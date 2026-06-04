<?php

namespace App\Http\Controllers;

use App\Models\FormPengujian;
use App\Models\FormVerification;
use App\Services\NotificationService;
use App\Enums\Role;
use Illuminate\Http\Request;

class KepalaUpaController extends Controller
{
    /**
     * Dashboard Kepala UPA - shows forms pending verification
     */
    public function dashboard()
    {
        // Forms waiting for initial UPA verification (penerimaan sampel)
        $pendingVerification = FormPengujian::with('admin', 'samples')
            ->where('status', 'verifikasi_upa_1')
            ->latest()
            ->get();

        // Forms waiting for signature (TTD)
        $pendingTtd = FormPengujian::with('admin', 'samples')
            ->where('status', 'ttd_upa')
            ->latest()
            ->get();

        // Recently verified by this user
        $recentlyVerified = FormPengujian::with('admin')
            ->whereIn('status', ['verifikasi_divisi', 'kirim_customer', 'selesai'])
            ->whereHas('verifications', function($q) {
                $q->where('verified_by', auth()->user()->user_id);
            })
            ->latest()
            ->take(10)
            ->get();

        return view('kepala-upa.dashboard', compact(
            'pendingVerification', 
            'pendingTtd', 
            'recentlyVerified'
        ));
    }

    /**
     * Approve form - moves to next status
     */
    public function approve(FormPengujian $form)
    {
        // Increase PHP execution time limit for Google Docs API calls
        set_time_limit(300);

        $currentStatus = $form->status;
        
        // Determine next status based on current
        $nextStatus = match($currentStatus) {
            'verifikasi_upa_1' => 'verifikasi_divisi',
            'ttd_upa' => 'kirim_customer',
            default => null,
        };

        if (!$nextStatus) {
            return back()->with('error', 'Status form tidak valid untuk diverifikasi');
        }

        // Update form status
        $updateData = [
            'status' => $nextStatus,
            'rejection_note' => null,
            'rejected_by' => null,
            'rejected_at' => null,
        ];
        
        // Set LHP signature timestamp when signing at ttd_upa stage
        if ($currentStatus === 'ttd_upa') {
            $updateData['lhp_signed_upa_at'] = now();
        }
        
        $form->update($updateData);

        // Log verification
        FormVerification::create([
            'form_pengujian_id' => $form->id,
            'action' => 'approve',
            'from_status' => $currentStatus,
            'to_status' => $nextStatus,
            'verified_by' => auth()->user()->user_id,
        ]);

        // Generate Signed SPU when approving verifikasi_upa_1
        // Note: SP3 documents are generated later when Kadiv signs the SPU
        if ($currentStatus === 'verifikasi_upa_1') {
            try {
                $googleDocsService = new \App\Services\GoogleDocsService();
                
                // IMPORTANT: Generate signed SPU FIRST before deleting the old one
                // This prevents data loss if generation fails
                $signedSpu = $googleDocsService->generateSpuSigned($form);
                
                // Only delete old unsigned SPU AFTER successful generation
                if ($form->spu_unsigned_doc_id) {
                    try {
                        $googleDocsService->deleteFile($form->spu_unsigned_doc_id);
                    } catch (\Exception $e) {
                        \Log::warning("Failed to delete unsigned SPU: " . $e->getMessage());
                        // Non-critical, continue anyway
                    }
                }
                
                $form->update([
                    'spu_unsigned_doc_id' => null,
                    'spu_signed_doc_id' => $signedSpu['id'],
                    'spu_signed_at' => now(),
                ]);
                
                // SP3 documents will be generated when Kepala Divisi signs the SPU
                
            } catch (\Exception $e) {
                \Log::error("Failed to generate SPU document: " . $e->getMessage());
                // IMPORTANT: Don't silently fail - inform the user
                return back()->with('error', 'Gagal generate SPU: ' . $e->getMessage());
            }
        }

        // Send notifications based on next status
        $notificationService = new NotificationService();
        if ($nextStatus === 'verifikasi_divisi') {
            $notificationService->notifyFormPending($form, Role::KEPALA_DIVISI, 'assignment analis dan verifikasi');
        } elseif ($nextStatus === 'kirim_customer') {
            $notificationService->notifyFormPending($form, Role::ADMIN, 'pengiriman ke customer');
        }

        $message = $currentStatus === 'ttd_upa' 
            ? 'Dokumen berhasil ditandatangani' 
            : 'Form berhasil diverifikasi';

        return redirect()->route('kepala-upa.dashboard')
            ->with('success', $message);
    }

    /**
     * Generate SP3 documents grouped by parameter
     */
    private function generateSp3Documents(FormPengujian $form, $googleDocsService): void
    {
        $form->load(['samples.sampleParameters.parameter']);
        
        // Group samples by parameter
        $parameterGroups = [];
        
        foreach ($form->samples as $sample) {
            foreach ($sample->sampleParameters as $sp) {
                if ($sp->parameter) {
                    $paramId = $sp->parameter->id;
                    
                    if (!isset($parameterGroups[$paramId])) {
                        $parameterGroups[$paramId] = [
                            'parameter' => $sp->parameter,
                            'samples' => [],
                        ];
                    }
                    
                    $parameterGroups[$paramId]['samples'][] = [
                        'sample_id' => $sample->id,
                        'sample_code' => $sample->sample_code,
                        'sample_name' => $sample->sample_name,
                    ];
                }
            }
        }
        
        // Generate SP3 for each parameter group
        foreach ($parameterGroups as $paramId => $group) {
            // Generate SP3 number
            $sp3Number = \App\Models\Sp3Document::generateNextNumber();
            
            // Generate SP3 document
            $sp3Result = $googleDocsService->generateSp3WithTable(
                $sp3Number,
                $group['samples'],
                $group['parameter']->name,
                $googleDocsService->generatePerihal($form),
                $form->no_spu ?? $form->form_number,
                null // NO_SPPP will be filled by Kadiv
            );
            
            // Create SP3 record in database
            $sp3 = \App\Models\Sp3Document::create([
                'form_pengujian_id' => $form->id,
                'parameter_id' => $paramId,
                'sp3_number' => $sp3Number,
                'google_doc_id' => $sp3Result['id'],
                'status' => 'draft',
            ]);
            
            // Create SP3 samples junction records
            foreach ($group['samples'] as $sample) {
                \App\Models\Sp3Sample::create([
                    'sp3_document_id' => $sp3->id,
                    'sample_id' => $sample['sample_id'],
                ]);
            }
        }
    }

    /**
     * Reject form - moves back to previous status
     */
    public function reject(Request $request, FormPengujian $form)
    {
        $request->validate([
            'note' => 'required|string|max:1000',
        ]);

        $currentStatus = $form->status;
        
        // Determine previous status
        $prevStatus = match($currentStatus) {
            'verifikasi_upa_1' => 'draft',
            'ttd_upa' => 'input_lhp', // Kembalikan ke Admin untuk perbaikan LHP
            default => null,
        };

        if (!$prevStatus) {
            return back()->with('error', 'Status form tidak valid untuk ditolak');
        }

        // Update form status
        $form->update([
            'status' => $prevStatus,
            'rejection_note' => $request->note,
            'rejected_by' => auth()->user()->user_id,
            'rejected_at' => now(),
        ]);

        // Log rejection
        FormVerification::create([
            'form_pengujian_id' => $form->id,
            'action' => 'reject',
            'from_status' => $currentStatus,
            'to_status' => $prevStatus,
            'verified_by' => auth()->user()->user_id,
            'note' => $request->note,
        ]);

        return redirect()->route('kepala-upa.dashboard')
            ->with('success', 'Form berhasil ditolak dengan catatan');
    }

    /**
     * View form detail
     */
    public function show(FormPengujian $form)
    {
        $form->load('samples.sampleParameters.parameter', 'samples.sampleParameters.analysisResult', 'verifications.verifier');
        
        return view('kepala-upa.show', compact('form'));
    }
}
