<?php

namespace App\Http\Controllers;

use App\Models\FormPengujian;
use Illuminate\Http\Request;
use Carbon\Carbon;

class FormListController extends Controller
{
    /**
     * Display a listing of all forms with filters
     * Accessible by all roles except Super Admin
     */
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

        // Deadline range filter
        if ($request->filled('deadline_from')) {
            $query->where('deadline_date', '>=', $request->deadline_from);
        }
        if ($request->filled('deadline_to')) {
            $query->where('deadline_date', '<=', $request->deadline_to);
        }

        // Received date range filter
        if ($request->filled('received_from')) {
            $query->where('received_date', '>=', $request->received_from);
        }
        if ($request->filled('received_to')) {
            $query->where('received_date', '<=', $request->received_to);
        }

        // Quick filters
        if ($request->filled('quick')) {
            switch ($request->quick) {
                case 'deadline_3days':
                    $query->whereNotIn('status', ['selesai', 'ditolak'])
                          ->where('deadline_date', '>=', Carbon::today())
                          ->where('deadline_date', '<=', Carbon::today()->addDays(3));
                    break;
                case 'deadline_week':
                    $query->whereNotIn('status', ['selesai', 'ditolak'])
                          ->where('deadline_date', '>=', Carbon::today())
                          ->where('deadline_date', '<=', Carbon::today()->addWeek());
                    break;
                case 'overdue':
                    $query->whereNotIn('status', ['selesai', 'ditolak'])
                          ->where('deadline_date', '<', Carbon::today());
                    break;
                case 'today':
                    $query->whereDate('received_date', Carbon::today());
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

        return view('form-list.index', compact('forms', 'statusCounts', 'statusOptions'));
    }

    /**
     * Show form detail (read-only for non-admin)
     */
    public function show(FormPengujian $form)
    {
        $form->load([
            'samples.sampleParameters.parameter',
            'samples.sampleParameters.analysisResult',
            'admin',
            'assignedAnalyst',
            'verifications.verifier',
            'sp3Documents'
        ]);

        return view('form-list.show', compact('form'));
    }
}
