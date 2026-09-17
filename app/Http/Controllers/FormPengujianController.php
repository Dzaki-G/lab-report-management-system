<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FormPengujian;
use App\Models\FormVerification;
use App\Models\Sample;
use App\Models\SampleParameter;
use App\Models\Parameter;
use App\Models\Sp3Document;
use App\Models\Sp3Sample;
use App\Services\NotificationService;
use App\Jobs\GenerateSp3Job;
use App\Enums\Role;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FormPengujianController extends Controller
{
    public function index(Request $request)
    {
        $query = FormPengujian::with(['samples.sampleParameters.parameter', 'admin', 'sp3Documents.parameter']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('no_terima_sampel', 'like', "%{$search}%")
                  ->orWhere('lhp_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('status', '!=', 'selesai');
            } else {
                $query->where('status', $request->status);
            }
        }

        if ($request->filled('stage')) {
            $query->where('status', $request->stage);
        }

        if ($request->filled('quick')) {
            switch ($request->quick) {
                case 'deadline_3days':
                    $query->where('status', '!=', 'selesai')
                          ->where('deadline_date', '>=', Carbon::today())
                          ->where('deadline_date', '<=', Carbon::today()->addDays(3));
                    break;
                case 'overdue':
                    $query->where('status', '!=', 'selesai')
                          ->where('deadline_date', '<', Carbon::today());
                    break;
            }
        }

        $sortBy = $request->get('sort', 'deadline_date');
        $sortDir = $request->get('dir', 'asc');

        if (in_array($sortBy, ['deadline_date', 'received_date', 'no_terima_sampel', 'lhp_number', 'customer_name', 'status'])) {
            $query->orderBy($sortBy, $sortDir);
        }

        $forms = $query->paginate(15)->withQueryString();

        $statusCounts = [
            'total' => FormPengujian::count(),
            'active' => FormPengujian::where('status', '!=', 'selesai')->count(),
            'selesai' => FormPengujian::where('status', 'selesai')->count(),
            'overdue' => FormPengujian::where('status', '!=', 'selesai')
                            ->where('deadline_date', '<', Carbon::today())->count(),
        ];

        // New, shorter status set — no SPU/UPA-signing stages, no separate kirim-customer step
        $statusOptions = [
            'dalam_pengujian' => 'Dalam Pengujian',
            'menunggu_review_divisi' => 'Menunggu Review Divisi',
            'ttd_upa' => 'Menunggu TTD Kepala UPA',
            'selesai' => 'Selesai',
        ];

        return view('form.index', compact('forms', 'statusCounts', 'statusOptions'));
    }

    public function create()
    {
        $parameters = Parameter::active()->orderBy('name')->get();
        $nextLhpNumber     = FormPengujian::generateLhpNumber();
        $nextSpppSeq       = Sp3Document::generateNextSpppSeq();
        $spppSuffix        = Sp3Document::spppSuffix();
        $nextSampleCodeSeq = Sample::generateNextSampleCodeSeq();
        $sampleCodeSuffix  = Sample::sampleCodeSuffix();
        return view('form.create', compact('parameters', 'nextLhpNumber', 'nextSpppSeq', 'spppSuffix', 'nextSampleCodeSeq', 'sampleCodeSuffix'));
    }

    /**
     * Create the form AND its SP3 documents in one step — no signing gate,
     * no Google Docs/Drive calls at all (nothing is generated until Kepala
     * Divisi approves the final LHP later in the workflow).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'lhp_number'  => 'nullable|string|max:100',
            'no_terima_sampel' => 'nullable|string',
            'received_date'  => 'required|date',
            'customer_name'  => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'customer_address' => 'nullable|string|max:500',
            'customer_institution' => 'nullable|string|max:255',
            'customer_position' => 'nullable|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'sample_type'    => 'nullable|string|max:255',
            'sample_matrix'  => 'nullable|string|max:255',
            'sample_name_label' => 'nullable|string|max:255',
            'sample_form'    => 'nullable|string|max:100',
            'sample_packing' => 'nullable|string|max:100',
            'sample_count'   => 'nullable|integer|min:1',
            'samples'        => 'required|array|min:1',
            'samples.*.sample_code' => 'required|string',
            'samples.*.sample_name' => 'required|string',
            'samples.*.notes'       => 'nullable|string',
            'samples.*.parameters'  => 'required|array|min:1',
            'lhp_number'  => 'nullable|string|max:100',
            'sp3_details' => 'nullable|array',
            'sp3_details.*.parameter_id' => 'required_with:sp3_details|integer|exists:parameters,id',
            'sp3_details.*.no_sppp' => 'required_with:sp3_details|string|max:100',
            'sp3_details.*.ik' => 'nullable|string|max:100',
        ]);

        $receivedDate = Carbon::parse($validated['received_date']);
        $form = null;

        DB::transaction(function () use ($validated, $receivedDate, $request, &$form) {
            $form = FormPengujian::create([
                'form_number'      => FormPengujian::generateFormNumber(),
                'lhp_number'       => $validated['lhp_number'] ?? null,
                'no_terima_sampel' => $validated['no_terima_sampel'] ?? null,
                'received_date'    => $validated['received_date'],
                'deadline_date'    => $receivedDate->copy()->addWeekdays(12),
                'customer_name'    => $validated['customer_name'] ?? null,
                'customer_phone'   => $validated['customer_phone'] ?? null,
                'customer_address' => $validated['customer_address'] ?? null,
                'customer_institution' => $validated['customer_institution'] ?? null,
                'customer_position' => $validated['customer_position'] ?? null,
                'contact_person'   => $validated['contact_person'] ?? null,
                'sample_type'      => $validated['sample_type'] ?? null,
                'sample_matrix'    => $validated['sample_matrix'] ?? null,
                'sample_name_label' => $validated['sample_name_label'] ?? null,
                'sample_form'      => $validated['sample_form'] ?? null,
                'sample_packing'   => $validated['sample_packing'] ?? null,
                'sample_count'     => $validated['sample_count'] ?? null,
                'admin_id'         => $request->user()->user_id,
                'status'           => 'dalam_pengujian',
            ]);

            FormVerification::create([
                'form_pengujian_id' => $form->id,
                'action' => 'buat_form',
                'from_status' => null,
                'to_status' => 'dalam_pengujian',
                'verified_by' => $request->user()->user_id,
            ]);

            // Create samples + their sample_parameters
            $samplesByParameter = []; // parameter_id => [ ['sample_id', 'sample_code', 'sample_name'], ... ]

            foreach ($validated['samples'] as $sampleData) {
                $sample = Sample::create([
                    'form_pengujian_id' => $form->id,
                    'sample_code'       => $sampleData['sample_code'],
                    'sample_name'       => $sampleData['sample_name'],
                    'notes'             => $sampleData['notes'] ?? null,
                ]);

                foreach ($sampleData['parameters'] as $parameterId) {
                    $parameter = Parameter::find($parameterId);

                    SampleParameter::create([
                        'sample_id'    => $sample->id,
                        'parameter_id' => $parameterId,
                        'method'       => $parameter?->default_method,
                        'status'       => 'pending',
                    ]);

                    $samplesByParameter[$parameterId][] = [
                        'sample_id'   => $sample->id,
                        'sample_code' => $sample->sample_code,
                        'sample_name' => $sample->sample_name,
                    ];
                }
            }

            // Index the admin's manually entered SP3 details by parameter_id for quick lookup
            $sp3DetailsByParameter = collect($validated['sp3_details'] ?? [])
                ->keyBy('parameter_id');

            // Create one SP3 DB record per parameter group.
            foreach ($samplesByParameter as $parameterId => $samples) {
                $detail = $sp3DetailsByParameter->get($parameterId, []);

                $sp3 = Sp3Document::create([
                    'form_pengujian_id' => $form->id,
                    'parameter_id'      => $parameterId,
                    'sp3_number'        => $detail['sp3_number'] ?? Sp3Document::generateNextNumber(),
                    'no_sppp'           => $detail['no_sppp'] ?? null,
                    'ik'                => $detail['ik'] ?? null,
                    'status'            => 'draft',
                    'review_status'     => 'pending',
                ]);

                foreach ($samples as $sample) {
                    Sp3Sample::create([
                        'sp3_document_id' => $sp3->id,
                        'sample_id'       => $sample['sample_id'],
                    ]);
                }
            }

            // Notify all analysts that new work is available (collective — no assignment/claiming)
            $notificationService = new NotificationService();
            $notificationService->notifyFormPending($form, Role::ANALIS, 'pengujian sampel baru');
        });

        // Dispatch one job per SP3 — non-blocking, no timeout risk
        if ($form) {
            $form->load('sp3Documents');
            foreach ($form->sp3Documents as $sp3) {
                $sp3->update(['doc_generation_status' => 'queued']);
                GenerateSp3Job::dispatch($sp3->id);
            }
        }

        return redirect()->route('form.index')
            ->with('success', 'Form Pengujian berhasil dibuat. Dokumen SP3 sedang digenerate di background.');
    }

    public function show(FormPengujian $form)
    {
        $form->load(
            'samples.sampleParameters.parameter',
            'samples.sampleParameters.filledByAnalyst',
            'samples.sampleParameters.analysisResult',
            'sp3Documents.parameter',
            'sp3Documents.assignedAnalyst'
        );

        $analysts = \App\Models\User::where('role_id', Role::ANALIS)
            ->where('is_active', true)
            ->orderBy('full_name')
            ->get();

        return view('form.show', compact('form', 'analysts'));
    }

    public function assignAnalyst(Request $request, Sp3Document $sp3)
    {
        $request->validate([
            'analyst_id' => 'nullable|exists:users,user_id',
        ]);

        $analystId = $request->analyst_id ?: null;

        // Verify it's an active analis if provided
        if ($analystId) {
            $analyst = \App\Models\User::where('user_id', $analystId)
                ->where('role_id', Role::ANALIS)
                ->where('is_active', true)
                ->firstOrFail();
        }

        // Reset view stamps on reassignment
        $sp3->update([
            'assigned_analyst_id' => $analystId,
            'assigned_at'         => $analystId ? now() : null,
            'first_viewed_at'     => null,
            'last_viewed_at'      => null,
        ]);

        // Notify the newly assigned analyst directly (single user, not whole role)
        if ($analystId) {
            $form = $sp3->formPengujian;
            $notificationService = new NotificationService();
            $notificationService->create(
                $analystId,
                'form_pending',
                'SP3 Ditugaskan ke Anda',
                "Anda ditugaskan untuk SP3 {$sp3->sp3_number} (Parameter: {$sp3->parameter?->name}) pada form {$form->form_number}.",
                ['form_id' => $form->id]
            );
        }

        return back()->with('success', $analystId
            ? 'Analis berhasil ditugaskan ke SP3 ' . $sp3->sp3_number . '.'
            : 'Penugasan analis pada SP3 ' . $sp3->sp3_number . ' dibatalkan.');
    }

    public function edit(FormPengujian $form)
    {
        $form->load('samples.sampleParameters');
        $parameters = Parameter::active()->orderBy('name')->get();

        return view('form.edit', compact('form', 'parameters'));
    }

    public function update(Request $request, FormPengujian $form)
    {
        $validated = $request->validate([
            'customer_name' => 'required|string',
            'received_date' => 'required|date',
        ]);

        $receivedDate = Carbon::parse($validated['received_date']);

        $form->update([
            'customer_name' => $validated['customer_name'],
            'received_date' => $validated['received_date'],
            'deadline_date' => $receivedDate->copy()->addWeekdays(12),
        ]);

        return redirect()->route('form.show', $form)
            ->with('success', 'Form Pengujian berhasil diupdate');
    }

    /**
     * Admin can edit an SP3's no_sppp/IK any time after creation.
     * Updates the SP3 record and regenerates the Google Doc so the
     * live document reflects the new SPPP/IK values.
     */
    public function lhpReady()
    {
        $ready = FormPengujian::where('status', 'kirim_customer')
            ->with('admin')
            ->latest('lhp_signed_upa_at')
            ->get();

        $recentDone = FormPengujian::where('status', 'selesai')
            ->with('admin')
            ->latest('updated_at')
            ->limit(20)
            ->get();

        return view('admin.lhp-ready', compact('ready', 'recentDone'));
    }

    public function markSent(FormPengujian $form)
    {
        if ($form->status !== 'kirim_customer') {
            return back()->with('error', 'Form tidak dalam status siap kirim.');
        }

        $form->update(['status' => 'selesai']);

        FormVerification::create([
            'form_pengujian_id' => $form->id,
            'action'            => 'kirim_customer',
            'from_status'       => 'kirim_customer',
            'to_status'         => 'selesai',
            'verified_by'       => auth()->user()->user_id,
        ]);

        return back()->with('success', 'LHP berhasil dikirim ke customer. Form ditandai selesai.');
    }

    public function sp3DocStatus(Sp3Document $sp3)
    {
        $sp3->refresh();
        return response()->json([
            'status'       => $sp3->doc_generation_status,
            'google_doc_id' => $sp3->google_doc_id,
            'error'        => $sp3->doc_generation_error,
        ]);
    }

    public function retrySp3Doc(Sp3Document $sp3)
    {
        if ($sp3->google_doc_id) {
            return back()->with('info', 'Dokumen SP3 sudah ada.');
        }

        $sp3->update([
            'doc_generation_status' => 'queued',
            'doc_generation_error'  => null,
        ]);

        GenerateSp3Job::dispatch($sp3->id);

        return back()->with('success', "SP3 {$sp3->sp3_number} sedang digenerate ulang.");
    }

    public function updateSp3Info(Request $request, Sp3Document $sp3)
    {
        $request->validate([
            'no_sppp' => 'nullable|string|max:100',
            'ik' => 'nullable|string|max:100',
        ]);

        $sp3->update([
            'no_sppp' => $request->no_sppp,
            'ik' => $request->ik,
        ]);

        return back()->with('success', 'Info SP3 (SPPP & IK) berhasil disimpan.');
    }

    /**
     * Admin manually set/override the LHP number on an existing form.
     */
    public function updateLhpNumber(Request $request, FormPengujian $form)
    {
        $request->validate([
            'lhp_number' => 'required|string|max:100',
        ]);

        $form->update(['lhp_number' => $request->lhp_number]);

        return back()->with('success', 'Nomor LHP berhasil diperbarui.');
    }

    /**
     * Delete a form. No more SPU/per-SP3 documents to clean up from Drive —
     * only the final LHP doc (if one was ever generated) needs deleting.
     * Only accessible by Admin.
     */
    public function destroy(FormPengujian $form)
    {
        $googleDocsService = new \App\Services\GoogleDocsService();
        $deletedDocs = [];
        $failedDocs = [];

        // Delete each SP3's Google Doc
        foreach ($form->sp3Documents as $sp3) {
            if ($sp3->google_doc_id) {
                try {
                    $googleDocsService->deleteFile($sp3->google_doc_id);
                    $deletedDocs[] = "SP3 {$sp3->sp3_number}";
                } catch (\Exception $e) {
                    Log::warning("Failed to delete SP3 {$sp3->sp3_number}: " . $e->getMessage());
                    $failedDocs[] = "SP3 {$sp3->sp3_number}";
                }
            }
        }

        // Delete the LHP doc if one was ever generated
        if ($form->lhp_google_file_id) {
            try {
                $googleDocsService->deleteFile($form->lhp_google_file_id);
                $deletedDocs[] = 'LHP';
            } catch (\Exception $e) {
                Log::warning("Failed to delete LHP doc for form {$form->form_number}: " . $e->getMessage());
                $failedDocs[] = 'LHP';
            }
        }

        $formNumber = $form->form_number;

        // Notifications store form_id in JSON, so they don't auto-cascade
        \App\Models\Notification::whereJsonContains('data->form_id', $form->id)->delete();

        // Cascades to samples, sample_parameters, sp3_documents, sp3_samples, verifications
        $form->delete();

        Log::info("Form {$formNumber} deleted by admin " . auth()->user()->user_id . ". Deleted docs: " . implode(', ', $deletedDocs));

        $message = "Form {$formNumber} berhasil dihapus.";
        if (!empty($deletedDocs)) {
            $message .= ' Dokumen terhapus: ' . implode(', ', $deletedDocs) . '.';
        }
        if (!empty($failedDocs)) {
            $message .= ' Gagal hapus dari Drive (sudah dihapus manual?): ' . implode(', ', $failedDocs) . '.';
        }

        return redirect()->route('form.index')->with('success', $message);
    }
}