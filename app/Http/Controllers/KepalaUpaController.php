<?php

namespace App\Http\Controllers;

use App\Models\FormPengujian;
use App\Models\FormVerification;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;

class KepalaUpaController extends Controller
{
    /**
     * Dashboard — forms waiting for UPA's signature on the LHP.
     * By the time a form reaches here, Kepala Divisi has already
     * approved every SP3 and the LHP doc already exists.
     */
    public function dashboard()
    {
        $pendingTtd = FormPengujian::with('admin', 'samples')
            ->where('status', 'ttd_upa')
            ->latest()
            ->get();

        $recentlySigned = FormPengujian::with('admin')
            ->where('status', 'selesai')
            ->whereHas('verifications', function ($q) {
                $q->where('verified_by', auth()->user()->user_id)
                  ->where('action', 'sign_lhp');
            })
            ->latest()
            ->take(10)
            ->get();

        return view('kepala-upa.dashboard', compact('pendingTtd', 'recentlySigned'));
    }

    /**
     * View form/LHP detail before signing.
     */
    public function show(FormPengujian $form)
    {
        $form->load(
            'samples.sampleParameters.parameter',
            'samples.sampleParameters.analysisResult',
            'samples.sampleParameters.filledByAnalyst',
            'sp3Documents.parameter',
            'verifications.verifier'
        );

        return view('kepala-upa.show', compact('form'));
    }

    /**
     * Sign the LHP. This is a lightweight text patch on the EXISTING doc
     * (filling in the UPA name/date placeholder) — never a new file, never
     * a delete+regenerate. There is no reject option here by design: once
     * Divisi has approved, UPA can only sign.
     */
    public function signLhp(FormPengujian $form)
    {
        if ($form->status !== 'ttd_upa') {
            return back()->with('error', 'Form tidak dalam status menunggu TTD Kepala UPA.');
        }

        if (!$form->lhp_google_file_id) {
            return back()->with('error', 'Dokumen LHP belum tersedia untuk form ini.');
        }

        try {
            $googleDocsService = new \App\Services\GoogleDocsService();
            $signatureUri = null;
            if (auth()->user()->signature_drive_file_id) {
                $signatureUri = "https://drive.google.com/uc?export=view&id=" . auth()->user()->signature_drive_file_id;
            }
            $googleDocsService->patchLhpSignature(
                $form->lhp_google_file_id,
                auth()->user()->full_name,
                now()->translatedFormat('d F Y'),
                $signatureUri
            );
        } catch (\Exception $e) {
            Log::error("Failed to patch LHP signature for form {$form->form_number}: " . $e->getMessage());
            return back()->with('error', 'Gagal menandatangani LHP: ' . $e->getMessage());
        }

        $form->update([
            'lhp_signed_upa_at' => now(),
            'status' => 'selesai',
        ]);

        FormVerification::create([
            'form_pengujian_id' => $form->id,
            'action' => 'sign_lhp',
            'from_status' => 'ttd_upa',
            'to_status' => 'selesai',
            'verified_by' => auth()->user()->user_id,
        ]);

        $notificationService = new NotificationService();
        $notificationService->notifyFormCompleted($form);

        return redirect()->route('kepala-upa.dashboard')
            ->with('success', 'LHP berhasil ditandatangani. Form selesai.');
    }
}