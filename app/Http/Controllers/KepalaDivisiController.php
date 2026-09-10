<?php

namespace App\Http\Controllers;

use App\Models\FormPengujian;
use App\Models\FormVerification;
use App\Models\Sp3Document;
use App\Models\SampleParameter;
use App\Services\NotificationService;
use App\Enums\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class KepalaDivisiController extends Controller
{
    /**
     * Dashboard — forms sitting in Divisi's review queue.
     * A form appears here once ALL its sample_parameters are 'done'
     * (that transition is triggered from AnalisController).
     */
    public function dashboard()
    {
        $pendingReview = FormPengujian::with('admin', 'sp3Documents.parameter')
            ->where('status', 'menunggu_review_divisi')
            ->latest()
            ->get();

        $recentlyApproved = FormPengujian::with('admin')
            ->whereIn('status', ['ttd_upa', 'selesai'])
            ->latest()
            ->take(10)
            ->get();

        return view('kepala-divisi.dashboard', compact('pendingReview', 'recentlyApproved'));
    }

    /**
     * Review page for a single form — the "table inside a table" view.
     * Shows every SP3 (parameter group) for this form, each with its own
     * review_status, rejection note (if any), and nested sample/method/result
     * table, matching the reference LHP table layout.
     */
    public function reviewLhp(FormPengujian $form)
    {
        $form->load([
            'samples.sampleParameters.parameter',
            'samples.sampleParameters.analysisResult',
            'samples.sampleParameters.filledByAnalyst',
            'sp3Documents.parameter',
            'sp3Documents.samples',
            'sp3Documents.rejectedByUser',
        ]);

        return view('kepala-divisi.review-lhp', compact('form'));
    }

    /**
     * Approve a single SP3. If this was the last one pending for the form,
     * this is also what triggers LHP generation — Divisi's approval IS the
     * signature, there's no separate signing step.
     */
    public function approveSp3(Sp3Document $sp3)
    {
        if (!in_array($sp3->review_status, [Sp3Document::STATUS_PENDING, Sp3Document::STATUS_RESUBMITTED])) {
            return back()->with('error', 'SP3 ini tidak dalam status yang bisa disetujui.');
        }

        if (!$sp3->allResultsFilled()) {
            return back()->with('error', 'Belum semua hasil pengujian untuk SP3 ini diisi.');
        }

        $sp3->markApproved();

        FormVerification::create([
            'form_pengujian_id' => $sp3->form_pengujian_id,
            'action' => 'sp3_approve',
            'from_status' => $sp3->form->status,
            'to_status' => $sp3->form->status,
            'verified_by' => auth()->user()->user_id,
            'note' => "SP3 {$sp3->sp3_number} disetujui",
        ]);

        try {
            $this->maybeGenerateLhp($sp3->form);
        } catch (\Exception $e) {
            Log::error("Failed to generate LHP after SP3 approval for form {$sp3->form_pengujian_id}: " . $e->getMessage());
            return back()->with('warning', "SP3 {$sp3->sp3_number} disetujui, tapi LHP gagal di-generate: " . $e->getMessage());
        }

        return back()->with('success', "SP3 {$sp3->sp3_number} disetujui.");
    }

    /**
     * Reject a single SP3 with a note. Only the analyst(s) who actually
     * entered results for this specific SP3 are notified — not everyone
     * on the form. The row stays visible in Divisi's queue (rejected ->
     * resubmitted once the analyst edits a result -> back to Divisi).
     */
    public function rejectSp3(Request $request, Sp3Document $sp3)
    {
        $request->validate([
            'note' => 'required|string|max:1000',
        ]);

        $sp3->markRejected($request->note, auth()->user()->user_id);

        FormVerification::create([
            'form_pengujian_id' => $sp3->form_pengujian_id,
            'action' => 'sp3_reject',
            'from_status' => $sp3->form->status,
            'to_status' => $sp3->form->status,
            'verified_by' => auth()->user()->user_id,
            'note' => "SP3 {$sp3->sp3_number}: {$request->note}",
        ]);

        // Notify only the analyst(s) who filled results for THIS SP3's parameter
        $analystIds = SampleParameter::whereIn('sample_id', $sp3->samples()->pluck('samples.id'))
            ->where('parameter_id', $sp3->parameter_id)
            ->whereNotNull('filled_by_analyst_id')
            ->pluck('filled_by_analyst_id')
            ->unique();

        $notificationService = new NotificationService();
        foreach ($analystIds as $analystId) {
            $notificationService->create(
                $analystId,
                'form_rejected',
                "SP3 {$sp3->sp3_number} ditolak",
                $request->note,
                ['form_id' => $sp3->form_pengujian_id, 'sp3_id' => $sp3->id]
            );
        }

        return back()->with('success', "SP3 {$sp3->sp3_number} dikembalikan ke analis dengan catatan.");
    }

    /**
     * Show a form's detail (read-only), including verification history.
     */
    public function show(FormPengujian $form)
    {
        $form->load(
            'samples.sampleParameters.parameter',
            'samples.sampleParameters.analysisResult',
            'sp3Documents.parameter',
            'verifications.verifier'
        );

        return view('kepala-divisi.show', compact('form'));
    }

    /**
     * Manual retry endpoint: generate the LHP for a form where all SP3s are approved.
     * Needed because maybeGenerateLhp() silently swallows Google Docs failures and
     * approved SP3s can't be re-approved, so without this there is no retry path.
     */
    public function generateLhp(FormPengujian $form)
    {
        $form->refresh();

        if ($form->lhp_google_file_id) {
            return back()->with('info', 'LHP sudah pernah di-generate.');
        }

        if (!$form->allSp3Approved()) {
            return back()->with('error', 'Belum semua SP3 disetujui.');
        }

        try {
            $this->maybeGenerateLhp($form);
        } catch (\Exception $e) {
            Log::error("Manual LHP generate failed for form {$form->form_number}: " . $e->getMessage());
            return back()->with('error', 'Gagal generate LHP: ' . $e->getMessage());
        }

        $form->refresh();
        if ($form->lhp_google_file_id) {
            return back()->with('success', 'LHP berhasil di-generate.');
        }

        return back()->with('error', 'Gagal generate LHP. Silakan coba lagi.');
    }

    /**
     * Generate the LHP once every SP3 for this form is approved.
     * This is the ONLY point in the whole lifecycle the LHP Google Doc
     * is created — never regenerated afterward. UPA's later signature
     * is a text patch on this same document, not a new file.
     */
    private function maybeGenerateLhp(FormPengujian $form): void
    {
        $form->refresh();

        if (!$form->allSp3Approved()) {
            return; // still waiting on other SP3s
        }

        $googleDocsService = new \App\Services\GoogleDocsService();
        $lhpResult = $googleDocsService->generateLhp($form, auth()->user()->full_name);

        $form->update([
            'lhp_google_file_id' => $lhpResult['id'],
            'lhp_uploaded_at' => now(),
            'lhp_signed_divisi_at' => now(),
            'status' => 'ttd_upa',
        ]);

        FormVerification::create([
            'form_pengujian_id' => $form->id,
            'action' => 'approve',
            'from_status' => 'menunggu_review_divisi',
            'to_status' => 'ttd_upa',
            'verified_by' => auth()->user()->user_id,
        ]);

        $notificationService = new NotificationService();
        $notificationService->notifyTtdRequest($form, Role::KEPALA_UPA);
    }
}