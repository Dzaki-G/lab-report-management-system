<?php

namespace App\Http\Controllers;

use App\Models\FormPengujian;
use App\Models\SampleParameter;
use App\Models\AnalysisResult;
use App\Models\Sp3Document;
use App\Models\Unit;
use App\Services\NotificationService;
use App\Enums\Role;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AnalisController extends Controller
{
    /**
     * Dashboard for analysts. One analyst owns one SP3 (assigned_analyst_id).
     * - Semua Tugas: every SP3 still open for work (not sent yet, or rejected) — visible to all.
     * - Tugas Saya:  SP3s owned by me, including ones sent and waiting for Divisi review.
     * Rejected SP3s stay listed even though the form is already in Divisi's queue.
     * Customer information is intentionally NOT loaded/shown here.
     */
    public function dashboard(Request $request)
    {
        $userId = auth()->user()->user_id;

        $activeSp3s = Sp3Document::with(['form', 'parameter', 'samples', 'assignedAnalyst'])
            ->whereHas('form', fn ($q) => $q->whereIn('status', ['dalam_pengujian', 'menunggu_review_divisi']))
            ->where('review_status', '!=', Sp3Document::STATUS_APPROVED)
            ->latest()
            ->get()
            ->map(fn ($sp3) => $this->attachSampleParameters($sp3));

        $allSp3s = $activeSp3s
            ->filter(fn ($sp3) => is_null($sp3->submitted_at) || $sp3->review_status === Sp3Document::STATUS_REJECTED)
            ->values();
        $mySp3s = $activeSp3s->where('assigned_analyst_id', $userId)->values();

        // History — SP3s I owned/sent (or filled, for data from before ownership) that are finished
        $historySp3s = Sp3Document::with(['form', 'parameter', 'samples'])
            ->where(function ($q) {
                $q->where('review_status', Sp3Document::STATUS_APPROVED)
                  ->orWhereHas('form', fn ($f) => $f->whereIn('status', ['ttd_upa', 'kirim_customer', 'selesai']));
            })
            ->where(function ($q) use ($userId) {
                $q->where('assigned_analyst_id', $userId)
                  ->orWhere('submitted_by', $userId)
                  ->orWhereHas('samples.sampleParameters', fn ($sp) => $sp->where('filled_by_analyst_id', $userId));
            })
            ->latest()
            ->take(20)
            ->get()
            ->map(fn ($sp3) => $this->attachSampleParameters($sp3));

        $activeTab = in_array($request->query('tab'), ['semua', 'saya', 'riwayat'])
            ? $request->query('tab')
            : ($mySp3s->isNotEmpty() ? 'saya' : 'semua');

        return view('analis.dashboard', compact('allSp3s', 'mySp3s', 'historySp3s', 'activeTab'));
    }

    private function attachSampleParameters(Sp3Document $sp3): Sp3Document
    {
        $sp3->setRelation('sampleParameters',
            $sp3->sampleParametersQuery()
                ->with(['sample', 'analysisResult', 'filledByAnalyst'])
                ->get()
        );
        return $sp3;
    }

    /**
     * Show a form for analysis. Any analyst can view any form in
     * 'dalam_pengujian' — no per-user access check anymore.
     * Customer info (name/phone/institution) should be omitted in this view.
     */
    public function showForm(FormPengujian $form)
    {
        $form->load([
            'samples.sampleParameters.parameter',
            'samples.sampleParameters.analysisResult',
            'samples.sampleParameters.filledByAnalyst',
            'sp3Documents.parameter',
            'sp3Documents.assignedAnalyst',
        ]);

        return view('analis.form-detail', compact('form'));
    }

    /**
     * Input result for a sample parameter — only the SP3 owner, while it's editable.
     */
    public function inputResult(SampleParameter $sampleParameter)
    {
        $sampleParameter->load(['sample.form', 'parameter', 'analysisResult']);

        $sp3 = $this->sp3For($sampleParameter);
        if ($error = $this->editBlockReason($sp3)) {
            return redirect()->route('analis.dashboard')->with('error', $error);
        }

        $this->recordSp3View($sp3);

        $units = Unit::orderBy('name')->get();

        $defaultInstrument = $sampleParameter->analysisResult?->instrument
            ?? $sampleParameter->parameter?->instrument
            ?? '';
        $defaultMethod = $sampleParameter->analysisResult?->method
            ?? $sampleParameter->method
            ?? $sampleParameter->parameter?->default_method
            ?? '';

        return view('analis.input-result', compact('sampleParameter', 'units', 'defaultInstrument', 'defaultMethod'));
    }

    /**
     * Simpan hasil pengujian. Only the SP3 owner may save, and only while the
     * SP3 is editable. Saving does NOT send anything to Divisi — the owner
     * does that explicitly with submitSp3().
     */
    public function storeResult(Request $request, SampleParameter $sampleParameter)
    {
        $sp3 = $this->sp3For($sampleParameter);
        if ($error = $this->editBlockReason($sp3)) {
            return redirect()->route('analis.dashboard')->with('error', $error);
        }

        $validated = $request->validate([
            'result_value'  => 'required|string',
            'result_unit'   => 'nullable|string',
            'instrument'    => 'required|string|max:255',
            'method'        => 'required|string|max:255',
            'analysis_date' => 'required|date',
            'notes'         => 'nullable|string',
        ]);

        AnalysisResult::updateOrCreate(
            ['sample_parameter_id' => $sampleParameter->id],
            [
                'result_value'  => $validated['result_value'],
                'result_unit'   => $validated['result_unit'] ?? null,
                'instrument'    => $validated['instrument'],
                'method'        => $validated['method'],
                'analyst_id'    => auth()->user()->user_id,
                'analysis_date' => $validated['analysis_date'],
                'notes'         => $validated['notes'] ?? null,
            ]
        );

        $sampleParameter->update([
            'status'               => 'done',
            'filled_by_analyst_id' => auth()->user()->user_id,
            'method'               => $validated['method'],
        ]);

        $message = $sp3->allResultsFilled()
            ? "Hasil disimpan. Semua sampel SP3 {$sp3->sp3_number} sudah terisi — klik \"Kirim ke Kepala Divisi\"."
            : 'Hasil pengujian berhasil disimpan';

        return redirect()->route('analis.dashboard', ['tab' => 'saya'])->with('success', $message);
    }

    /**
     * Take an untaken SP3. Atomic so two analysts can't grab the same one.
     */
    public function takeSp3(Sp3Document $sp3)
    {
        $sp3->load('form');
        if ($sp3->review_status === Sp3Document::STATUS_APPROVED
            || !in_array($sp3->form?->status, ['dalam_pengujian', 'menunggu_review_divisi'])) {
            return back()->with('error', 'SP3 ini sudah tidak aktif.');
        }

        $taken = Sp3Document::where('id', $sp3->id)
            ->whereNull('assigned_analyst_id')
            ->update([
                'assigned_analyst_id' => auth()->user()->user_id,
                'assigned_at'         => now(),
                'first_viewed_at'     => null,
                'last_viewed_at'      => null,
            ]);

        if ($taken === 0) {
            return back()->with('error', "SP3 {$sp3->sp3_number} sudah diambil analis lain.");
        }

        return redirect()->route('analis.dashboard', ['tab' => 'saya'])
            ->with('success', "SP3 {$sp3->sp3_number} berhasil diambil dan masuk ke Tugas Saya.");
    }

    /**
     * Give an SP3 back — only the owner, only before any result is entered.
     */
    public function releaseSp3(Sp3Document $sp3)
    {
        if (!$sp3->isOwnedBy(auth()->user()->user_id)) {
            return back()->with('error', 'Anda bukan pemegang SP3 ini.');
        }
        if ($sp3->submitted_at || $sp3->hasAnyResult()) {
            return back()->with('error', 'SP3 yang sudah ada hasil atau sudah dikirim tidak bisa dilepas.');
        }

        $sp3->update([
            'assigned_analyst_id' => null,
            'assigned_at'         => null,
            'first_viewed_at'     => null,
            'last_viewed_at'      => null,
        ]);

        return redirect()->route('analis.dashboard', ['tab' => 'semua'])
            ->with('success', "SP3 {$sp3->sp3_number} dilepas dan kembali ke Semua Tugas.");
    }

    /**
     * Owner sends the SP3 to Kepala Divisi. The form moves to Divisi's queue
     * once every SP3 on it has been sent. A resend after rejection notifies Divisi.
     */
    public function submitSp3(Sp3Document $sp3)
    {
        $user = auth()->user();

        if ($error = $this->editBlockReason($sp3)) {
            return back()->with('error', $error);
        }
        if (!$sp3->allResultsFilled()) {
            return back()->with('error', "Belum semua sampel di SP3 {$sp3->sp3_number} diisi.");
        }

        $wasRejected = $sp3->review_status === Sp3Document::STATUS_REJECTED;
        $sp3->markSubmitted($user->user_id);

        $form = $sp3->form()->with('sp3Documents')->first();

        \App\Models\FormVerification::create([
            'form_pengujian_id' => $form->id,
            'action'            => $wasRejected ? 'sp3_resubmit' : 'sp3_submit',
            'from_status'       => $form->status,
            'to_status'         => $form->status,
            'verified_by'       => $user->user_id,
            'note'              => "SP3 {$sp3->sp3_number} " . ($wasRejected ? 'dikirim ulang' : 'dikirim') . " oleh {$user->full_name}",
        ]);

        $notificationService = new NotificationService();

        if ($form->status === 'dalam_pengujian') {
            if ($form->sp3Documents->every(fn ($s) => !is_null($s->submitted_at))) {
                $form->update(['status' => 'menunggu_review_divisi']);

                \App\Models\FormVerification::create([
                    'form_pengujian_id' => $form->id,
                    'action'            => 'selesai_pengujian',
                    'from_status'       => 'dalam_pengujian',
                    'to_status'         => 'menunggu_review_divisi',
                    'verified_by'       => $user->user_id,
                ]);

                $notificationService->notifyFormPending($form, Role::KEPALA_DIVISI, 'review hasil pengujian');

                return redirect()->route('analis.dashboard', ['tab' => 'saya'])
                    ->with('success', "SP3 {$sp3->sp3_number} dikirim. Semua SP3 form ini sudah dikirim — form masuk review Kepala Divisi.");
            }

            return redirect()->route('analis.dashboard', ['tab' => 'saya'])
                ->with('success', "SP3 {$sp3->sp3_number} dikirim. Form akan masuk review setelah semua SP3 dikirim.");
        }

        // Form already in Divisi's queue — this is a resend after rejection
        $notificationService->createForRole(
            Role::KEPALA_DIVISI,
            'form_pending',
            "SP3 {$sp3->sp3_number} dikirim ulang",
            "SP3 {$sp3->sp3_number} (Parameter: {$sp3->parameter?->name}) sudah diperbaiki dan dikirim ulang oleh {$user->full_name}.",
            ['form_id' => $form->id, 'sp3_id' => $sp3->id]
        );

        return redirect()->route('analis.dashboard', ['tab' => 'saya'])
            ->with('success', "SP3 {$sp3->sp3_number} dikirim ulang ke Kepala Divisi.");
    }

    private function sp3For(SampleParameter $sampleParameter): ?Sp3Document
    {
        return Sp3Document::where('form_pengujian_id', $sampleParameter->sample->form_pengujian_id)
            ->where('parameter_id', $sampleParameter->parameter_id)
            ->first();
    }

    /**
     * Why the current analyst may not change this SP3 right now, or null if they may.
     */
    private function editBlockReason(?Sp3Document $sp3): ?string
    {
        if (!$sp3) {
            return 'SP3 untuk parameter ini tidak ditemukan.';
        }
        if (!$sp3->isOwnedBy(auth()->user()->user_id)) {
            return $sp3->assigned_analyst_id
                ? 'SP3 ini dipegang analis lain.'
                : 'Ambil SP3 ini terlebih dahulu sebelum menginput hasil.';
        }
        if ($sp3->review_status === Sp3Document::STATUS_APPROVED) {
            return 'SP3 ini sudah disetujui, hasil tidak bisa diubah lagi.';
        }
        if (!$sp3->isEditable()) {
            return 'SP3 ini sudah dikirim dan sedang menunggu review Kepala Divisi.';
        }
        return null;
    }

    /**
     * Submit/update LCP link for an SP3. Fully optional, non-blocking,
     * open to any analyst, addable any time — even after the form is done.
     * No status transition is triggered by this anymore.
     */
    public function submitLcpLink(Request $request, Sp3Document $sp3)
    {
        $request->validate([
            'lcp_link' => 'required|url|max:500',
        ], [
            'lcp_link.required' => 'Link LCP wajib diisi',
            'lcp_link.url' => 'Format link tidak valid',
        ]);

        $sp3->update([
            'lcp_google_file_url' => $request->lcp_link,
            'lcp_uploaded_at' => now(),
        ]);

        return back()->with('success', 'Link LCP berhasil disimpan');
    }

    /**
     * Save the GDrive link to this SP3's pictures (one per parameter). Allowed
     * anytime. Only written into the LHP doc at generation time — a later
     * change shows on the website (Admin's Kirim Customer page) only.
     */
    public function submitPhotoLink(Request $request, Sp3Document $sp3)
    {
        if (!$sp3->canSetPhotoLink(auth()->user()->user_id)) {
            return back()->with('error', 'Hanya analis pemegang SP3 ini yang dapat mengisi link foto.');
        }

        // Optional per parameter — an empty submit removes the link
        $request->validate([
            'photo_link' => 'nullable|url|max:500',
        ], [
            'photo_link.url' => 'Format link tidak valid',
        ]);

        $link = $request->filled('photo_link') ? $request->photo_link : null;

        $sp3->update([
            'photo_link' => $link,
            'photo_link_updated_at' => now(),
        ]);

        $message = $link ? 'Link foto berhasil disimpan.' : 'Link foto dihapus.';
        if ($sp3->form?->lhp_google_file_id) {
            $message .= ' (LHP sudah digenerate — link baru tampil di website, tidak mengubah dokumen LHP.)';
        }

        return back()->with('success', $message);
    }

    /**
     * Show SP3 details — the "attached file" analysts reference while working,
     * plus the LCP input. Open to any analyst, no ownership check.
     */
    public function showSp3(Sp3Document $sp3)
    {
        $sp3->load(['form', 'parameter', 'samples']);

        $this->recordSp3View($sp3);

        return view('analis.sp3-detail', compact('sp3'));
    }

    private function recordSp3View(Sp3Document $sp3): void
    {
        if ((int) $sp3->assigned_analyst_id !== (int) auth()->user()->user_id) {
            return;
        }

        if (is_null($sp3->first_viewed_at)) {
            $sp3->first_viewed_at = now();
        }
        $sp3->last_viewed_at = now();
        $sp3->saveQuietly();
    }
}