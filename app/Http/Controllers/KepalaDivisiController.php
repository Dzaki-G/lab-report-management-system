<?php

namespace App\Http\Controllers;

use App\Models\FormPengujian;
use App\Models\FormVerification;
use App\Models\Sp3Document;
use App\Models\SampleParameter;
use App\Services\NotificationService;
use App\Jobs\GenerateLhpJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        $inProgress = FormPengujian::with('admin')
            ->where('status', 'dalam_pengujian')
            ->latest()
            ->get();

        $recentlyApproved = FormPengujian::with('admin')
            ->whereIn('status', ['ttd_upa', 'kirim_customer', 'selesai'])
            ->latest()
            ->get();

        return view('kepala-divisi.dashboard', compact('pendingReview', 'inProgress', 'recentlyApproved'));
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

        $this->maybeDispatchLhpJob($sp3->form);

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

    public function generateLhp(FormPengujian $form)
    {
        $form->refresh();

        if ($form->lhp_google_file_id) {
            return back()->with('info', 'LHP sudah pernah di-generate.');
        }

        if (!$form->allSp3Approved()) {
            return back()->with('error', 'Belum semua SP3 disetujui.');
        }

        $dispatched = $this->maybeDispatchLhpJob($form);

        if (!$dispatched) {
            return back()->with('info', 'LHP sedang dalam proses generate.');
        }

        return back()->with('success', 'LHP sedang di-generate, halaman akan diperbarui otomatis.');
    }

    public function lhpStatus(FormPengujian $form)
    {
        $form->refresh();
        return response()->json([
            'status'         => $form->lhp_generation_status,
            'lhp_file_id'    => $form->lhp_google_file_id,
            'error'          => $form->lhp_generation_error,
        ]);
    }

    /**
     * Atomically claim the "generating" slot then dispatch the job.
     * Returns true if dispatched, false if already in-progress/done.
     */
    private function maybeDispatchLhpJob(FormPengujian $form): bool
    {
        $form->refresh();

        if (!$form->allSp3Approved()) {
            return false;
        }

        if ($form->lhp_google_file_id) {
            return false;
        }

        // Atomic check-and-set: only one process wins this update
        $claimed = DB::table('form_pengujian')
            ->where('id', $form->id)
            ->where(function ($q) {
                $q->whereNull('lhp_generation_status')
                  ->orWhere('lhp_generation_status', 'failed');
            })
            ->update([
                'lhp_generation_status'     => 'queued',
                'lhp_generation_started_at' => now(),
            ]);

        if ($claimed === 0) {
            return false; // Another process already claimed it
        }

        GenerateLhpJob::dispatch(
            $form->id,
            auth()->user()->full_name,
            auth()->user()->user_id,
        );

        return true;
    }
}