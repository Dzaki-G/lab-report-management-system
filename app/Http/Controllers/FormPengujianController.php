<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FormPengujian;
use App\Models\FormVerification;
use App\Models\Sample;
use App\Models\SampleParameter;
use App\Models\Parameter;
use App\Services\NotificationService;
use App\Enums\Role;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;


class FormPengujianController extends Controller
{
    public function index(Request $request)
    {
        $query = FormPengujian::with(['samples.sampleParameters.parameter', 'admin', 'assignedAnalyst']);

        // Search filter (form number or customer name)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('form_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->whereNotIn('status', ['selesai', 'ditolak']);
            } elseif ($request->status === 'selesai') {
                $query->where('status', 'selesai');
            } elseif ($request->status === 'ditolak') {
                $query->where('status', 'ditolak');
            } else {
                $query->where('status', $request->status);
            }
        }

        // Workflow stage filter
        if ($request->filled('stage')) {
            $query->where('status', $request->stage);
        }

        // Quick filters
        if ($request->filled('quick')) {
            switch ($request->quick) {
                case 'deadline_3days':
                    $query->whereNotIn('status', ['selesai', 'ditolak'])
                          ->where('deadline_date', '>=', Carbon::today())
                          ->where('deadline_date', '<=', Carbon::today()->addDays(3));
                    break;
                case 'overdue':
                    $query->whereNotIn('status', ['selesai', 'ditolak'])
                          ->where('deadline_date', '<', Carbon::today());
                    break;
            }
        }

        // Sorting
        $sortBy = $request->get('sort', 'deadline_date');
        $sortDir = $request->get('dir', 'asc');
        
        if (in_array($sortBy, ['deadline_date', 'received_date', 'form_number', 'customer_name', 'status'])) {
            $query->orderBy($sortBy, $sortDir);
        }

        $forms = $query->paginate(15)->withQueryString();

        // Get status counts for summary
        $statusCounts = [
            'total' => FormPengujian::count(),
            'active' => FormPengujian::whereNotIn('status', ['selesai', 'ditolak'])->count(),
            'selesai' => FormPengujian::where('status', 'selesai')->count(),
            'overdue' => FormPengujian::whereNotIn('status', ['selesai', 'ditolak'])
                            ->where('deadline_date', '<', Carbon::today())->count(),
        ];

        // Status options for filter dropdown
        $statusOptions = [
            'draft' => 'Draft',
            'verifikasi_upa_1' => 'Verifikasi UPA',
            'verifikasi_divisi' => 'Verifikasi Divisi',
            'dalam_pengujian' => 'Dalam Pengujian',
            'verifikasi_hasil_divisi' => 'Ver. Hasil Divisi',
            'input_lhp' => 'Input LHP',
            'ttd_divisi_lhp' => 'TTD Divisi (LHP)',
            'ttd_upa' => 'TTD UPA',
            'kirim_customer' => 'Kirim Customer',
            'selesai' => 'Selesai',
            'ditolak' => 'Ditolak',
        ];

        return view('form.index', compact('forms', 'statusCounts', 'statusOptions'));
    }

    public function create()
    {
        $parameters = Parameter::active()->orderBy('name')->get();
        return view('form.create', compact('parameters'));
    }

    public function store(Request $request)
    {
        // Prepend 'SPU-' prefix for uniqueness check
        $formNumberWithPrefix = $request->form_number;
        if (!str_starts_with($formNumberWithPrefix, 'SPU-')) {
            $formNumberWithPrefix = 'SPU-' . $formNumberWithPrefix;
        }
        
        // Check if form_number already exists (with prefix)
        if (FormPengujian::where('form_number', $formNumberWithPrefix)->exists()) {
            return back()->withErrors(['form_number' => 'ID Form sudah digunakan.'])->withInput();
        }
        
        $validated = $request->validate([
            'form_number'    => 'required|string',
            'no_spu'         => 'nullable|string',
            'no_terima_sampel' => 'nullable|string',
            'received_date'  => 'required|date',
            'customer_name'  => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'samples'        => 'required|array|min:1',
            'samples.*.sample_code' => 'required|string',
            'samples.*.sample_name' => 'required|string',
            'samples.*.notes'       => 'nullable|string',
            'samples.*.parameters'  => 'required|array|min:1',
        ]);

        $receivedDate = Carbon::parse($validated['received_date']);
        $form = null;

        DB::transaction(function () use ($validated, $receivedDate, $request, &$form) {
            // Prepend 'SPU-' prefix if not already present
            $formNumber = $validated['form_number'];
            if (!str_starts_with($formNumber, 'SPU-')) {
                $formNumber = 'SPU-' . $formNumber;
            }
            
            // Create form pengujian
            $form = FormPengujian::create([
                'form_number'      => $formNumber,
                'no_spu'           => $validated['no_spu'] ?? null,
                'no_terima_sampel' => $validated['no_terima_sampel'] ?? null,
                'received_date'    => $validated['received_date'],
                'deadline_date'    => $receivedDate->copy()->addWeekdays(12),
                'customer_name'    => $validated['customer_name'] ?? null,
                'customer_phone'   => $validated['customer_phone'] ?? null,
                'admin_id'         => $request->user()->user_id,
                'status'           => 'verifikasi_upa_1',
            ]);

            // Log the submission
            FormVerification::create([
                'form_pengujian_id' => $form->id,
                'action' => 'submit',
                'from_status' => 'draft',
                'to_status' => 'verifikasi_upa_1',
                'verified_by' => $request->user()->user_id,
            ]);

            // Create samples and their parameters
            foreach ($validated['samples'] as $sampleData) {
                $sample = Sample::create([
                    'form_pengujian_id' => $form->id,
                    'sample_code'       => $sampleData['sample_code'],
                    'sample_name'       => $sampleData['sample_name'],
                    'notes'             => $sampleData['notes'] ?? null,
                ]);

                // Create sample parameters
                foreach ($sampleData['parameters'] as $parameterId) {
                    $parameter = Parameter::find($parameterId);
                    SampleParameter::create([
                        'sample_id'    => $sample->id,
                        'parameter_id' => $parameterId,
                        'method'       => $parameter?->default_method,
                        'status'       => 'pending',
                    ]);
                }
            }

            // Notify Kepala UPA about new form
            $notificationService = new NotificationService();
            $notificationService->notifyFormPending($form, Role::KEPALA_UPA, 'verifikasi penerimaan');
        });

        // Generate SPU Unsigned document (outside transaction for better error handling)
        $spuWarning = null;
        if ($form) {
            try {
                $googleDocsService = new \App\Services\GoogleDocsService();
                $spuResult = $googleDocsService->generateSpuWithTable($form);
                
                // Save document ID to form
                $form->update([
                    'spu_unsigned_doc_id' => $spuResult['id'],
                ]);
            } catch (\Exception $e) {
                // Log error but don't fail the form creation
                \Log::error("Failed to generate SPU document: " . $e->getMessage());
                $spuWarning = 'Form berhasil dibuat, tetapi dokumen SPU gagal di-generate (kemungkinan masalah jaringan). Silakan generate ulang melalui halaman detail form.';
            }
        }

        return redirect()->route('form.index')
            ->with('success', 'Form Pengujian berhasil dibuat')
            ->with('warning', $spuWarning);
    }

    public function show(FormPengujian $form)
    {
        $form->load('samples.sampleParameters.parameter', 'samples.sampleParameters.assignedAnalyst', 'sp3Documents.parameter');

        return view('form.show', compact('form'));
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
     * Daftar form menunggu input LHP (setelah verifikasi hasil divisi)
     */
    public function pendingInputLhp()
    {
        $forms = FormPengujian::with('samples.sampleParameters.parameter', 'samples.sampleParameters.analysisResult')
            ->where('status', 'input_lhp')
            ->latest()
            ->get();

        return view('form.pending-lhp-list', compact('forms'));
    }

    /**
     * Submit LHP - moves to ttd_divisi_lhp (TTD by Kepala Divisi first)
     */
    public function submitLhp(FormPengujian $form)
    {
        if ($form->status !== 'input_lhp') {
            return back()->with('error', 'Form tidak dalam status Input LHP');
        }

        $form->update(['status' => 'ttd_divisi_lhp']);

        FormVerification::create([
            'form_pengujian_id' => $form->id,
            'action' => 'approve',
            'from_status' => 'input_lhp',
            'to_status' => 'ttd_divisi_lhp',
            'verified_by' => auth()->user()->user_id,
        ]);

        // Notify Kepala Divisi about LHP ready for signature
        $notificationService = new NotificationService();
        $notificationService->notifyTtdRequest($form, Role::KEPALA_DIVISI);

        return redirect()->route('form.input-lhp')
            ->with('success', 'LHP berhasil disubmit, menunggu tanda tangan Kepala Divisi');
    }

    /**
     * Reject LHP - back to verifikasi_hasil_divisi
     */
    public function rejectLhp(Request $request, FormPengujian $form)
    {
        $request->validate(['note' => 'required|string']);

        if ($form->status !== 'input_lhp') {
            return back()->with('error', 'Form tidak dalam status Input LHP');
        }

        $form->update([
            'status' => 'verifikasi_hasil_divisi',
            'rejection_note' => $request->note,
            'rejected_by' => auth()->user()->user_id,
            'rejected_at' => now(),
        ]);

        FormVerification::create([
            'form_pengujian_id' => $form->id,
            'action' => 'reject',
            'from_status' => 'input_lhp',
            'to_status' => 'verifikasi_hasil_divisi',
            'verified_by' => auth()->user()->user_id,
            'note' => $request->note,
        ]);

        return redirect()->route('form.input-lhp')
            ->with('success', 'Form dikembalikan ke Kepala Divisi dengan catatan');
    }

    /**
     * Daftar form menunggu kirim ke customer
     */
    public function pendingKirim()
    {
        $forms = FormPengujian::with('samples')
            ->where('status', 'kirim_customer')
            ->latest()
            ->get();

        return view('form.kirim-customer', compact('forms'));
    }

    /**
     * Konfirmasi kirim ke customer - moves to selesai
     */
    public function konfirmasiKirim(FormPengujian $form)
    {
        if ($form->status !== 'kirim_customer') {
            return back()->with('error', 'Form tidak dalam status Kirim Customer');
        }

        $form->update(['status' => 'selesai']);

        FormVerification::create([
            'form_pengujian_id' => $form->id,
            'action' => 'approve',
            'from_status' => 'kirim_customer',
            'to_status' => 'selesai',
            'verified_by' => auth()->user()->user_id,
        ]);

        return redirect()->route('form.kirim-customer')
            ->with('success', 'Form telah selesai - hasil sudah dikirim ke customer');
    }

    /**
     * Submit LHP link for a form
     */
    public function submitLhpLink(Request $request, FormPengujian $form)
    {
        $request->validate([
            'lhp_link' => 'required|url|max:500',
        ], [
            'lhp_link.required' => 'Link LHP wajib diisi',
            'lhp_link.url' => 'Format link tidak valid',
        ]);

        // Update form record with link
        $form->update([
            'lhp_google_file_url' => $request->lhp_link,
            'lhp_uploaded_at' => now(),
        ]);
        
        return back()->with('success', 'Link LHP berhasil disimpan');
    }

    /**
     * View LHP input page
     */
    public function showInputLhp(FormPengujian $form)
    {
        // Load related data including LCP links from SP3s
        $form->load([
            'samples.sampleParameters.parameter',
            'sp3Documents.parameter',
            'sp3Documents.assignedAnalyst',
            'admin',
        ]);
        
        return view('form.input-lhp', compact('form'));
    }

    /**
     * Admin updates SP3 info (No. SPPP & IK) — non-blocking
     */
    public function updateSp3Info(Request $request, \App\Models\Sp3Document $sp3)
    {
        $request->validate([
            'no_sppp' => 'nullable|string|max:100',
            'ik' => 'nullable|string|max:100',
        ]);

        $sp3->update([
            'no_sppp' => $request->no_sppp,
            'ik' => $request->ik,
        ]);

        \Log::info("Admin SP3 Update Data:", [
            'sp3_id' => $sp3->id,
            'request_all' => $request->all(),
            'updated_no_sppp' => $sp3->no_sppp,
            'updated_ik' => $sp3->ik,
        ]);

        // Update Google Doc if it exists - By regenerating it entirely so table cells reflect the new IK
        if ($sp3->google_doc_id) {
            try {
                $googleDocsService = new \App\Services\GoogleDocsService();
                
                // Keep the old folder and ID reference for deletion
                $oldDocId = $sp3->google_doc_id;
                
                // Get samples structure needed for table generation
                $sp3->load(['samples', 'parameter', 'form', 'assignedAnalyst']);
                $samples = $sp3->samples->map(function($sample) {
                    return [
                        'sample_id' => $sample->id,
                        'sample_code' => $sample->sample_code,
                        'sample_name' => $sample->sample_name,
                    ];
                })->toArray();
                
                // Generate a fresh document
                $newDoc = $googleDocsService->generateSp3WithTable(
                    $sp3->sp3_number,
                    $samples,
                    $sp3->parameter->name ?? '',
                    $googleDocsService->generatePerihal($sp3->form),
                    $sp3->form->no_spu ?? $sp3->form->form_number,
                    $sp3->no_sppp,
                    $sp3->ik,
                    $sp3->assignedAnalyst->full_name ?? ''
                );
                
                // Update the doc ID in DB
                $sp3->update([
                    'google_doc_id' => $newDoc['id'],
                    'google_doc_url' => $newDoc['url'] ?? null
                ]);
                
                // Finally delete the old defective document to not clutter drive
                $googleDocsService->deleteFile($oldDocId);
                
                \Log::info("SP3 Document {$sp3->sp3_number} regenerated successfully.");
                
            } catch (\Exception $e) {
                \Log::error("Failed to regenerate SP3 Google Doc: " . $e->getMessage());
            }
        }

        return back()->with('success', 'Info SP3 (SPPP & IK) berhasil disimpan.');
    }

    /**
     * Delete a form and all its related Google Docs from Drive.
     * Only accessible by Admin.
     */
    public function destroy(FormPengujian $form)
    {
        $googleDocsService = new \App\Services\GoogleDocsService();
        $deletedDocs = [];
        $failedDocs = [];

        // Delete SPU documents from Google Drive
        foreach ([
            'spu_unsigned_doc_id' => 'SPU Unsigned',
            'spu_signed_doc_id'   => 'SPU Signed',
        ] as $field => $label) {
            if ($form->$field) {
                try {
                    $googleDocsService->deleteFile($form->$field);
                    $deletedDocs[] = $label;
                } catch (\Exception $e) {
                    \Log::warning("Failed to delete {$label} ({$form->$field}): " . $e->getMessage());
                    $failedDocs[] = $label;
                }
            }
        }

        // Delete all SP3 documents from Google Drive
        foreach ($form->sp3Documents as $sp3) {
            if ($sp3->google_doc_id) {
                try {
                    $googleDocsService->deleteFile($sp3->google_doc_id);
                    $deletedDocs[] = "SP3 {$sp3->sp3_number}";
                } catch (\Exception $e) {
                    \Log::warning("Failed to delete SP3 {$sp3->sp3_number}: " . $e->getMessage());
                    $failedDocs[] = "SP3 {$sp3->sp3_number}";
                }
            }
        }

        // Delete the LHP from Drive if it exists
        if ($form->lhp_google_file_id) {
            try {
                $googleDocsService->deleteFile($form->lhp_google_file_id);
                $deletedDocs[] = 'LHP';
            } catch (\Exception $e) {
                \Log::warning("Failed to delete LHP: " . $e->getMessage());
                $failedDocs[] = 'LHP';
            }
        }

        $formNumber = $form->form_number;

        // Delete from database (cascades to samples, parameters, verifications, sp3 docs, etc.)
        $form->delete();

        \Log::info("Form {$formNumber} deleted by admin " . auth()->user()->user_id . ". Deleted docs: " . implode(', ', $deletedDocs));

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

