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
     * Dashboard for analysts. No more "my forms" via assignment — any
     * analyst can see and work on any form currently in testing.
     * Customer information is intentionally NOT loaded/shown here.
     */
    public function dashboard()
    {
        $userId = auth()->user()->user_id;

        $allActiveSp3s = Sp3Document::with(['form', 'parameter', 'samples'])
            ->whereHas('form', fn ($q) => $q->where('status', 'dalam_pengujian'))
            ->latest()
            ->get()
            ->map(function ($sp3) use ($userId) {
                $sp3->setRelation('sampleParameters',
                    \App\Models\SampleParameter::with(['sample', 'analysisResult', 'filledByAnalyst'])
                        ->whereIn('sample_id', $sp3->samples->pluck('id'))
                        ->where('parameter_id', $sp3->parameter_id)
                        ->get()
                );
                return $sp3;
            });

        // Split: assigned to me vs unassigned (SP3s assigned to others are hidden)
        $mySp3s          = $allActiveSp3s->where('assigned_analyst_id', $userId)->values();
        $unassignedSp3s  = $allActiveSp3s->whereNull('assigned_analyst_id')->values();

        // History — SP3s where this analyst filled at least one result, form moved on
        $historySp3s = Sp3Document::with(['form', 'parameter', 'samples'])
            ->whereHas('form', fn ($q) => $q->whereIn('status', ['menunggu_review_divisi', 'ttd_upa', 'selesai']))
            ->whereHas('samples.sampleParameters', fn ($q) => $q->where('filled_by_analyst_id', $userId))
            ->latest()
            ->take(20)
            ->get()
            ->map(function ($sp3) use ($userId) {
                $sp3->setRelation('sampleParameters',
                    \App\Models\SampleParameter::with(['sample', 'analysisResult'])
                        ->whereIn('sample_id', $sp3->samples->pluck('id'))
                        ->where('parameter_id', $sp3->parameter_id)
                        ->where('filled_by_analyst_id', $userId)
                        ->get()
                );
                return $sp3;
            });

        return view('analis.dashboard', compact('mySp3s', 'unassignedSp3s', 'historySp3s'));
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
        ]);

        return view('analis.form-detail', compact('form'));
    }

    /**
     * Input result for a sample parameter — open to any analyst, no claiming.
     */
    public function inputResult(SampleParameter $sampleParameter)
    {
        $sampleParameter->load(['sample.form', 'parameter', 'analysisResult']);

        // Stamp view on the SP3 for this parameter if this analyst is assigned
        $sp3 = Sp3Document::where('form_pengujian_id', $sampleParameter->sample->form->id)
            ->where('parameter_id', $sampleParameter->parameter_id)
            ->first();
        if ($sp3) {
            $this->recordSp3View($sp3);
        }

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
     * Simpan hasil pengujian. Any analyst may submit — the analyst who
     * actually submits is recorded via filled_by_analyst_id (attribution,
     * not assignment/claiming).
     */
    public function storeResult(Request $request, SampleParameter $sampleParameter)
    {
        $form = $sampleParameter->sample->form;

        $sp3 = Sp3Document::where('form_pengujian_id', $form->id)
            ->where('parameter_id', $sampleParameter->parameter_id)
            ->first();

        // Once an SP3 has been approved, its data has already been baked into
        // the generated LHP — block further edits so the doc can't go stale.
        if ($sp3 && $sp3->review_status === Sp3Document::STATUS_APPROVED) {
            return back()->with('error', 'SP3 untuk parameter ini sudah disetujui, hasil tidak bisa diubah lagi.');
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

        // If this SP3 was previously rejected, editing a result auto-resubmits it
        // (confirmed default: no extra "resubmit" button, it just goes back to
        // Divisi's queue as soon as a fix is saved).
        if ($sp3) {
            $sp3->markResubmitted();
        }

        // Once every sample_parameter on the form is done, move to Divisi's review queue
        if ($form->allSamplesAnalyzed() && $form->status === 'dalam_pengujian') {
            $form->update(['status' => 'menunggu_review_divisi']);

            \App\Models\FormVerification::create([
                'form_pengujian_id' => $form->id,
                'action' => 'selesai_pengujian',
                'from_status' => 'dalam_pengujian',
                'to_status' => 'menunggu_review_divisi',
                'verified_by' => auth()->user()->user_id,
            ]);

            $notificationService = new NotificationService();
            $notificationService->notifyFormPending($form, Role::KEPALA_DIVISI, 'review hasil pengujian');

            return redirect()->route('analis.dashboard')
                ->with('success', 'Semua pengujian selesai! Form dikirim untuk review Kepala Divisi.');
        }

        return redirect()->route('analis.form.show', $form)
            ->with('success', 'Hasil pengujian berhasil disimpan');
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