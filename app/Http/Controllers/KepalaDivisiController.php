<?php

namespace App\Http\Controllers;

use App\Models\FormPengujian;
use App\Models\FormVerification;
use App\Models\SampleParameter;
use App\Models\User;
use App\Services\NotificationService;
use App\Enums\Role;
use Illuminate\Http\Request;

class KepalaDivisiController extends Controller
{
    /**
     * Dashboard Kepala Divisi - shows forms pending verification
     */
    public function dashboard()
    {
        // Forms waiting for initial divisi verification
        $pendingVerification = FormPengujian::with('admin', 'samples.sampleParameters')
            ->where('status', 'verifikasi_divisi')
            ->latest()
            ->get();

        // Forms waiting for result verification (after analis done)
        $pendingResultVerification = FormPengujian::with('admin', 'samples.sampleParameters.analysisResult')
            ->where('status', 'verifikasi_hasil_divisi')
            ->latest()
            ->get();

        // Forms waiting for LHP signature (after admin input LHP)
        $pendingLhpSignature = FormPengujian::with('admin')
            ->where('status', 'ttd_divisi_lhp')
            ->latest()
            ->get();

        // Recently verified by this user
        $recentlyVerified = FormPengujian::with('admin')
            ->whereIn('status', ['dalam_pengujian', 'ttd_upa', 'kirim_customer', 'selesai'])
            ->whereHas('verifications', function($q) {
                $q->where('verified_by', auth()->user()->user_id);
            })
            ->latest()
            ->take(10)
            ->get();

        return view('kepala-divisi.dashboard', compact(
            'pendingVerification', 
            'pendingResultVerification', 
            'pendingLhpSignature',
            'recentlyVerified'
        ));
    }

    /**
     * Approve form - moves to next status
     */


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
            'verifikasi_divisi' => 'verifikasi_upa_1',
            'verifikasi_hasil_divisi' => 'dalam_pengujian',
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

        // If rejecting LCP results, flag all SP3s for revision
        if ($currentStatus === 'verifikasi_hasil_divisi') {
            $form->sp3Documents()->update([
                'needs_revision' => true,
                'revision_note' => $request->note,
            ]);

            // Notify each assigned analyst
            $notificationService = new NotificationService();
            $analystIds = $form->sp3Documents()
                ->whereNotNull('assigned_analyst_id')
                ->pluck('assigned_analyst_id')
                ->unique();

            foreach ($analystIds as $analystId) {
                $notificationService->notifyFormPending($form, Role::ANALIS, 'revisi LCP - ' . $request->note);
            }
        }

        return redirect()->route('kepala-divisi.dashboard')
            ->with('success', 'Form berhasil ditolak dengan catatan');
    }

    /**
     * View form detail
     */
    public function show(FormPengujian $form)
    {
        $form->load(['samples.sampleParameters.parameter', 'samples.sampleParameters.analysisResult', 'samples.sampleParameters.assignedAnalyst', 'verifications.verifier', 'sp3Documents.parameter', 'sp3Documents.assignedAnalyst']);
        
        // Get list of analysts for assignment
        $analysts = User::where('role_id', Role::ANALIS)->get();
        
        return view('kepala-divisi.show', compact('form', 'analysts'));
    }

    /**
     * Sign SPU (Second Signature) + Generate SP3 Documents
     */
    public function signSpu(FormPengujian $form)
    {
        try {
            // In 3-template strategy, we generate a NEW document for the 2nd signature
            // The previous 'spu_signed_doc_id' was the one signed by UPA only.
            // We will overwrite it or handle it as the final version.

            $googleDocsService = new \App\Services\GoogleDocsService();
            
            // Generate the FULLY SIGNED document
            $doc = $googleDocsService->generateSpuSignedFull($form);
            
            // Delete the old "UPA Only" signed document to avoid duplication
            if ($form->spu_signed_doc_id) {
                try {
                    $googleDocsService->deleteFile($form->spu_signed_doc_id);
                    \Log::info("Deleted old SPU document: {$form->spu_signed_doc_id}");
                } catch (\Exception $e) {
                    \Log::warning("Failed to delete old SPU document: " . $e->getMessage());
                }
            }
            
            // Update DB with the new Document ID (This is now the final SPU)
            $form->update([
                'spu_signed_doc_id' => $doc['id'],
                'spu_signed_divisi_at' => now(), // Track when Divisi signed
            ]);
            
            // Now generate SP3 documents (grouped by parameter)
            $this->generateSp3Documents($form, $googleDocsService);

            return back()->with('success', 'Dokumen SPU berhasil ditandatangani dan SP3 telah di-generate');
        } catch (\Exception $e) {
            \Log::error("Failed to sign SPU Full: " . $e->getMessage());
            return back()->with('error', 'Gagal menandatangani SPU: ' . $e->getMessage());
        }
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
            
            // Generate SP3 document (unsigned template)
            $sp3Result = $googleDocsService->generateSp3WithTable(
                $sp3Number,
                $group['samples'],
                $group['parameter']->name,
                $googleDocsService->generatePerihal($form),
                $form->no_spu ?? $form->form_number,
                null // NO_SPPP will be filled by Kadiv or Admin later
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
        
        \Log::info("Generated " . count($parameterGroups) . " SP3 documents for form {$form->form_number}");
    }

    /**
     * Update SP3 - Assign Analyst (Kepala Divisi only assigns analyst)
     */
    public function updateSp3(Request $request, \App\Models\Sp3Document $sp3)
    {
        $request->validate([
            'assigned_analyst_id' => 'required|exists:users,user_id',
        ]);

        try {
            $analyst = User::where('user_id', $request->assigned_analyst_id)->first();
            
            // 1. Update Database (Sp3Document)
            $sp3->update([
                'assigned_analyst_id' => $request->assigned_analyst_id,
                'status' => 'assigned',
            ]);

            // 2. Update Google Doc with analyst name
            if ($sp3->google_doc_id) {
                $googleDocsService = new \App\Services\GoogleDocsService();
                $googleDocsService->updateSp3Info(
                    $sp3->google_doc_id,
                    $sp3->no_sppp,
                    $sp3->ik,
                    $analyst->full_name
                );
            }

            // 3. Update SampleParameter
            $sampleIds = $sp3->samples()->pluck('samples.id');
            
            \App\Models\SampleParameter::whereIn('sample_id', $sampleIds)
                ->where('parameter_id', $sp3->parameter_id)
                ->update([
                    'assigned_analyst_id' => $request->assigned_analyst_id,
                ]);

            return back()->with('success', 'Analis berhasil ditugaskan untuk SP3');
        } catch (\Exception $e) {
            \Log::error("Failed to update SP3: " . $e->getMessage());
            return back()->with('error', 'Gagal update SP3: ' . $e->getMessage());
        }
    }

    /**
     * Final Approval (after all SP3s are ready)
     */
    public function approve(FormPengujian $form)
    {
        // Check 1: SPU must be signed by Kepala Divisi
        if (!$form->spu_signed_divisi_at) {
            return back()->with('error', 'SPU belum ditandatangani. Silakan tanda tangani SPU terlebih dahulu.');
        }
        
        // Check 2: All SP3s must have assigned analyst
        $unassignedSp3 = $form->sp3Documents()->whereNull('assigned_analyst_id')->count();
        
        if ($unassignedSp3 > 0) {
            return back()->with('error', "Masih ada $unassignedSp3 dokumen SP3 yang belum di-assign analis.");
        }
        
        // Check 3: Must have at least one SP3
        if ($form->sp3Documents()->count() == 0) {
            return back()->with('error', 'Tidak ada dokumen SP3 yang tersedia. Pastikan SPU sudah diapprove oleh Kepala UPA.');
        }

        // Proceed with approval logic (similar to old approve)
        $nextStatus = 'dalam_pengujian';
        
        // Generate signed SP3 documents (switch to signed template)
        $googleDocsService = new \App\Services\GoogleDocsService();
        $sp3Documents = $form->sp3Documents;
        
        foreach ($sp3Documents as $sp3) {
            try {
                // Delete old unsigned SP3 (optional, but cleaner)
                if ($sp3->google_doc_id) {
                    try {
                        $googleDocsService->deleteFile($sp3->google_doc_id);
                    } catch (\Exception $e) {
                        \Log::warning("Failed to delete old SP3: " . $e->getMessage());
                    }
                }
                
                // Generate new signed SP3
                $signedSp3 = $googleDocsService->generateSp3Signed($sp3);
                
                // Update SP3 record with new doc ID
                $sp3->update([
                    'google_doc_id' => $signedSp3['id'],
                    'google_doc_url' => $signedSp3['url'],
                    'signed_at' => now(),
                ]);
                
                \Log::info("Successfully generated signed SP3: {$sp3->sp3_number}");
            } catch (\Exception $e) {
                \Log::error("Failed to generate signed SP3 {$sp3->sp3_number}: " . $e->getMessage());
                // Continue with other SP3s, don't fail the whole approval
            }
        }
        
        $form->update([
            'status' => $nextStatus,
            'rejection_note' => null,
            'rejected_by' => null,
            'rejected_at' => null,
        ]);

        FormVerification::create([
            'form_pengujian_id' => $form->id,
            'action' => 'approve',
            'from_status' => 'verifikasi_divisi',
            'to_status' => $nextStatus,
            'verified_by' => auth()->user()->user_id,
        ]);

        // Notify analysts
        $notificationService = new NotificationService();
        // Notify based on SP3 assignments
        $assignedAnalysts = $sp3Documents->pluck('assigned_analyst_id')->unique()->filter();
        
        foreach ($assignedAnalysts as $analystId) {
            $notificationService->notifyAssignment($analystId, $form, 'SP3 Assignment');
        }

        return redirect()->route('kepala-divisi.dashboard')->with('success', 'Form berhasil disetujui, SP3 ditandatangani, dan tugas dikirim ke analis.');
    }

    /**
     * Approve LCP - verifies LCP from analis and moves to Admin for LHP input
     */
    public function approveLcp(FormPengujian $form)
    {
        // Verify form is in correct status
        if ($form->status !== 'verifikasi_hasil_divisi') {
            return back()->with('error', 'Form tidak dalam status verifikasi hasil divisi.');
        }

        // Check that all SP3s have LCP submitted
        $sp3sWithoutLcp = $form->sp3Documents()->whereNull('lcp_google_file_url')->count();
        
        if ($sp3sWithoutLcp > 0) {
            return back()->with('error', "Masih ada $sp3sWithoutLcp SP3 yang belum memiliki LCP.");
        }

        // Move to next status - Admin input LHP
        $form->update([
            'status' => 'input_lhp',
        ]);

        FormVerification::create([
            'form_pengujian_id' => $form->id,
            'action' => 'approve_lcp',
            'from_status' => 'verifikasi_hasil_divisi',
            'to_status' => 'input_lhp',
            'verified_by' => auth()->user()->user_id,
        ]);

        // Notify admin
        $notificationService = new NotificationService();
        $notificationService->notifyFormPending($form, Role::ADMIN, 'input LHP');

        return redirect()->route('kepala-divisi.dashboard')
            ->with('success', 'LCP diverifikasi. Form dikirim ke Admin untuk input LHP.');
    }

    /**
     * Sign LHP - Kepala Divisi signs the LHP document and moves to Kepala UPA for final signature
     */
    public function signLhp(FormPengujian $form)
    {
        // Verify form is in correct status
        if ($form->status !== 'ttd_divisi_lhp') {
            return back()->with('error', 'Form tidak dalam status TTD Kepala Divisi (LHP).');
        }

        // Check that LHP link exists
        if (empty($form->lhp_google_file_url) && empty($form->lhp_link)) {
            return back()->with('error', 'LHP belum diinput oleh Admin.');
        }

        // Move to next status - Kepala UPA TTD
        $form->update([
            'status' => 'ttd_upa',
            'lhp_signed_divisi_at' => now(),
        ]);

        FormVerification::create([
            'form_pengujian_id' => $form->id,
            'action' => 'sign_lhp',
            'from_status' => 'ttd_divisi_lhp',
            'to_status' => 'ttd_upa',
            'verified_by' => auth()->user()->user_id,
        ]);

        // Notify Kepala UPA
        $notificationService = new NotificationService();
        $notificationService->notifyTtdRequest($form, Role::KEPALA_UPA);

        return redirect()->route('kepala-divisi.dashboard')
            ->with('success', 'LHP ditandatangani. Form dikirim ke Kepala UPA untuk tanda tangan akhir.');
    }

    /**
     * Old assignAndApprove method - Deprecated/Replaced
     */
    public function assignAndApproveOld(Request $request, FormPengujian $form)
    {
        // Validate that form is in correct status
        if ($form->status !== 'verifikasi_divisi') {
            return back()->with('error', 'Status form tidak valid untuk diverifikasi');
        }

        // Validate analyst assignments
        $request->validate([
            'analyst' => 'required|array',
            'analyst.*' => 'required|exists:users,user_id',
        ], [
            'analyst.required' => 'Pilih analis untuk setiap parameter',
            'analyst.*.required' => 'Pilih analis untuk setiap parameter',
        ]);

        // Assign analysts to each sample parameter
        foreach ($request->analyst as $sampleParameterId => $analystId) {
            SampleParameter::where('id', $sampleParameterId)->update([
                'assigned_analyst_id' => $analystId,
            ]);
        }

        // Update form status
        $form->update([
            'status' => 'dalam_pengujian',
            'rejection_note' => null,
            'rejected_by' => null,
            'rejected_at' => null,
        ]);

        // Log verification
        FormVerification::create([
            'form_pengujian_id' => $form->id,
            'action' => 'approve',
            'from_status' => 'verifikasi_divisi',
            'to_status' => 'dalam_pengujian',
            'verified_by' => auth()->user()->user_id,
            'note' => 'Analis telah di-assign ke parameter',
        ]);

        // Notify each assigned analyst
        $notificationService = new NotificationService();
        $form->load('samples.sampleParameters.parameter');
        foreach ($request->analyst as $sampleParameterId => $analystId) {
            $sp = SampleParameter::with('parameter')->find($sampleParameterId);
            if ($sp) {
                $notificationService->notifyAssignment(
                    $analystId,
                    $form,
                    $sp->parameter->name ?? 'Parameter'
                );
            }
        }

        return redirect()->route('kepala-divisi.dashboard')
            ->with('success', 'Form berhasil diverifikasi dan analis telah ditugaskan');
    }
}
