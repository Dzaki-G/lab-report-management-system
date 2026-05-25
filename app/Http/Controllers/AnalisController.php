<?php

namespace App\Http\Controllers;

use App\Models\FormPengujian;
use App\Models\SampleParameter;
use App\Models\AnalysisResult;
use App\Models\Unit;
use App\Services\NotificationService;
use App\Enums\Role;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AnalisController extends Controller
{
    /**
     * Dashboard untuk analis - menampilkan form dengan parameter yang ditugaskan
     */
    public function dashboard()
    {
        $userId = auth()->user()->user_id;

        // Get forms that have parameters assigned to this analyst
        $myForms = FormPengujian::with(['samples.sampleParameters.parameter', 'admin'])
            ->where('status', 'dalam_pengujian')
            ->whereHas('samples.sampleParameters', function($q) use ($userId) {
                $q->where('assigned_analyst_id', $userId);
            })
            ->latest()
            ->get();

        // Forms completed (where all parameters assigned to this analyst are done)
        $completedForms = FormPengujian::with(['samples', 'admin'])
            ->whereIn('status', ['verifikasi_hasil_divisi', 'input_lhp', 'ttd_upa', 'kirim_customer', 'selesai'])
            ->whereHas('samples.sampleParameters', function($q) use ($userId) {
                $q->where('assigned_analyst_id', $userId);
            })
            ->latest()
            ->take(10)
            ->get();

        // Get SP3 documents assigned to this analyst
        $mySp3s = \App\Models\Sp3Document::with(['form', 'parameter'])
            ->where('assigned_analyst_id', $userId)
            ->whereHas('form', function($q) {
                $q->where('status', 'dalam_pengujian');
            })
            ->latest()
            ->get();

        return view('analis.dashboard', compact('myForms', 'completedForms', 'mySp3s'));
    }

    /**
     * Show form detail for analysis
     */
    public function showForm(FormPengujian $form)
    {
        $userId = auth()->user()->user_id;
        
        // Verify this analyst has at least one parameter assigned in this form
        $hasAccess = $form->samples->flatMap->sampleParameters
            ->where('assigned_analyst_id', $userId)->isNotEmpty();
            
        if (!$hasAccess) {
            abort(403, 'Anda tidak memiliki akses ke form ini');
        }

        $form->load([
            'samples.sampleParameters.parameter',
            'samples.sampleParameters.analysisResult',
            'samples.sampleParameters.assignedAnalyst',
            'admin'
        ]);

        return view('analis.form-detail', compact('form'));
    }

    /**
     * Input result for a sample parameter
     */
    public function inputResult(SampleParameter $sampleParameter)
    {
        // Verify this parameter is assigned to current analis
        if ($sampleParameter->assigned_analyst_id != auth()->user()->user_id) {
            abort(403, 'Anda tidak memiliki akses ke parameter ini');
        }

        $sampleParameter->load(['sample.form', 'parameter', 'analysisResult']);
        
        // Get all units for dropdown
        $units = Unit::orderBy('name')->get();

        return view('analis.input-result', compact('sampleParameter', 'units'));
    }

    /**
     * Start working on a sample parameter
     */
    public function startWork(SampleParameter $sampleParameter)
    {
        // Verify this parameter is assigned to current analis
        if ($sampleParameter->assigned_analyst_id != auth()->user()->user_id) {
            abort(403);
        }

        $sampleParameter->update(['status' => 'in_progress']);

        return redirect()->route('analis.input', $sampleParameter)
            ->with('success', 'Pengujian dimulai');
    }

    /**
     * Simpan hasil pengujian
     */
    public function storeResult(Request $request, SampleParameter $sampleParameter)
    {
        // Verify this parameter is assigned to current analis
        if ($sampleParameter->assigned_analyst_id != auth()->user()->user_id) {
            abort(403);
        }
        
        $form = $sampleParameter->sample->form;

        $validated = $request->validate([
            'result_value' => 'required|string',
            'result_unit' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        // Create or update analysis result
        AnalysisResult::updateOrCreate(
            ['sample_parameter_id' => $sampleParameter->id],
            [
                'result_value' => $validated['result_value'],
                'result_unit' => $validated['result_unit'] ?? null,
                'analyst_id' => auth()->user()->user_id,
                'analysis_date' => Carbon::now(),
                'notes' => $validated['notes'] ?? null,
            ]
        );

        // Update status to done
        $sampleParameter->update(['status' => 'done']);

        // Check if ALL sample parameters in this form are done
        if ($form->allSamplesAnalyzed() && $form->status === 'dalam_pengujian') {
            // Upgrade form status to next verification stage
            $form->update(['status' => 'verifikasi_hasil_divisi']);

            // Log the status change
            \App\Models\FormVerification::create([
                'form_pengujian_id' => $form->id,
                'action' => 'selesai_pengujian',
                'from_status' => 'dalam_pengujian',
                'to_status' => 'verifikasi_hasil_divisi',
                'verified_by' => auth()->user()->user_id,
            ]);

            // Notify Kepala Divisi about completed testing
            $notificationService = new NotificationService();
            $notificationService->notifyFormPending($form, Role::KEPALA_DIVISI, 'verifikasi hasil pengujian');

            return redirect()->route('analis.dashboard')
                ->with('success', 'Semua pengujian selesai! Form dikirim untuk verifikasi hasil.');
        }

        return redirect()->route('analis.form.show', $form)
            ->with('success', 'Hasil pengujian berhasil disimpan');
    }

    /**
     * Submit LCP link for an SP3
     */
    public function submitLcpLink(Request $request, \App\Models\Sp3Document $sp3)
    {
        // Verify this SP3 is assigned to current analis
        if ($sp3->assigned_analyst_id != auth()->user()->user_id) {
            abort(403, 'Anda tidak memiliki akses ke SP3 ini');
        }

        $request->validate([
            'lcp_link' => 'required|url|max:500',
        ], [
            'lcp_link.required' => 'Link LCP wajib diisi',
            'lcp_link.url' => 'Format link tidak valid',
        ]);

        // Update SP3 record with link and clear revision flag
        $sp3->update([
            'lcp_google_file_url' => $request->lcp_link,
            'lcp_uploaded_at' => now(),
            'needs_revision' => false,
            'revision_note' => null,
        ]);

        // Update related SampleParameters status to 'done'
        $form = $sp3->form;
        \App\Models\SampleParameter::whereHas('sample', function($q) use ($form) {
            $q->where('form_pengujian_id', $form->id);
        })
        ->where('parameter_id', $sp3->parameter_id)
        ->update(['status' => 'done']);

        // Check if ALL SP3s have LCP AND no SP3 still needs revision
        $allSp3s = $form->sp3Documents()->get();
        $allHaveLcp = $allSp3s->every(fn($doc) => !empty($doc->lcp_google_file_url));
        $anyNeedsRevision = $allSp3s->contains(fn($doc) => $doc->needs_revision);

        if ($allHaveLcp && !$anyNeedsRevision && $form->status === 'dalam_pengujian') {
            // All LCPs submitted and no pending revisions - move to next stage
            $form->update([
                'status' => 'verifikasi_hasil_divisi',
            ]);

            // Log the status change
            \App\Models\FormVerification::create([
                'form_pengujian_id' => $form->id,
                'action' => 'submit_lcp',
                'from_status' => 'dalam_pengujian',
                'to_status' => 'verifikasi_hasil_divisi',
                'verified_by' => auth()->user()->user_id,
            ]);
            
            // Notify Kepala Divisi about completed testing
            $notificationService = new NotificationService();
            $notificationService->notifyFormPending($form, Role::KEPALA_DIVISI, 'verifikasi hasil pengujian');
            
            return redirect()->route('analis.dashboard')
                ->with('success', 'Link LCP berhasil disimpan. Semua LCP sudah lengkap - form dikirim ke Kepala Divisi untuk verifikasi.');
        }
        
        // Check remaining revisions for user feedback
        $remainingRevisions = $allSp3s->where('needs_revision', true)->count();
        if ($remainingRevisions > 0) {
            return back()->with('success', "Link LCP berhasil disimpan. Masih ada {$remainingRevisions} SP3 lain yang perlu direvisi.");
        }

        return back()->with('success', 'Link LCP berhasil disimpan');
    }

    /**
     * Show SP3 details for LCP upload
     */
    public function showSp3(\App\Models\Sp3Document $sp3)
    {
        // Verify this SP3 is assigned to current analis
        if ($sp3->assigned_analyst_id != auth()->user()->user_id) {
            abort(403, 'Anda tidak memiliki akses ke SP3 ini');
        }
        
        $sp3->load(['form', 'parameter', 'samples']);
        
        return view('analis.sp3-detail', compact('sp3'));
    }
}
