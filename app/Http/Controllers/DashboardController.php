<?php

namespace App\Http\Controllers;

use App\Models\FormPengujian;
use App\Models\SampleParameter;
use App\Enums\Role;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $role = $user->role_id;

        // Base data for all roles
        $data = [
            'user' => $user,
            'role' => $role,
        ];

        // Role-specific dashboard data
        switch ($role) {
            case Role::SUPER_ADMIN:
                $data = array_merge($data, $this->getSuperAdminData());
                break;
            case Role::ADMIN:
                $data = array_merge($data, $this->getAdminData());
                $data = array_merge($data, $this->getFormListData($request));
                break;
            case Role::KEPALA_UPA:
                $data = array_merge($data, $this->getKepalaUpaData());
                $data = array_merge($data, $this->getFormListData($request));
                break;
            case Role::KEPALA_DIVISI:
                $data = array_merge($data, $this->getKepalaDivisiData());
                $data = array_merge($data, $this->getFormListData($request));
                break;
            case Role::ANALIS:
                $data = array_merge($data, $this->getAnalisData());
                $data = array_merge($data, $this->getFormListData($request));
                break;
        }

        return view('dashboard', $data);
    }

    private function getSuperAdminData()
    {
        return [
            'totalUsers' => \App\Models\User::count(),
            'activeUsers' => \App\Models\User::where('is_active', true)->count(),
            'totalForms' => FormPengujian::count(),
        ];
    }

    /**
     * Clear all form data (Super Admin only, for testing purposes)
     */
    public function clearAllForms(Request $request)
    {
        $request->validate([
            'confirmation' => 'required|in:HAPUS SEMUA',
        ], [
            'confirmation.in' => 'Ketik "HAPUS SEMUA" untuk konfirmasi.',
        ]);

        \DB::transaction(function () {
            \App\Models\AnalysisResult::truncate();
            \App\Models\SampleParameter::truncate();
            \App\Models\Sp3Sample::truncate();
            \App\Models\Sp3Document::truncate();
            \App\Models\Sample::truncate();
            \App\Models\FormVerification::truncate();
            \App\Models\Notification::truncate();
            FormPengujian::truncate();
        });

        return redirect()->route('dashboard')
            ->with('success', 'Semua data form berhasil dihapus.');
    }

    private function getAdminData()
    {
        $statusCounts = FormPengujian::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $upcomingDeadlines = FormPengujian::where('deadline_date', '>=', Carbon::today())
            ->where('deadline_date', '<=', Carbon::today()->addDays(3))
            ->whereNotIn('status', ['selesai', 'ditolak'])
            ->with('samples')
            ->orderBy('deadline_date')
            ->take(5)
            ->get();

        $pendingValidation = FormPengujian::where('status', 'validasi_admin')->count();
        $totalActive = FormPengujian::whereNotIn('status', ['selesai', 'ditolak'])->count();
        $totalSelesai = FormPengujian::where('status', 'selesai')->count();

        // Forms in testing status but not claimed by any analis
        $formsNeedingAssignment = FormPengujian::with('samples')
            ->where('status', 'dalam_pengujian')
            ->whereNull('assigned_analyst_id')
            ->get();

        return [
            'statusCounts' => $statusCounts,
            'upcomingDeadlines' => $upcomingDeadlines,
            'totalActive' => $totalActive,
            'totalSelesai' => $totalSelesai,
            'formsNeedingAssignment' => $formsNeedingAssignment,
        ];
    }

    private function getKepalaUpaData()
    {
        $pendingVerifikasi1 = FormPengujian::where('status', 'verifikasi_upa_1')->count();
        $pendingTtd = FormPengujian::where('status', 'ttd_upa')->count();

        return [
            'pendingVerifikasi1' => $pendingVerifikasi1,
            'pendingTtd' => $pendingTtd,
            'totalPending' => $pendingVerifikasi1 + $pendingTtd,
        ];
    }

    private function getKepalaDivisiData()
    {
        $pendingVerifikasi = FormPengujian::where('status', 'verifikasi_divisi')->count();
        $pendingVerifikasiHasil = FormPengujian::where('status', 'verifikasi_hasil_divisi')->count();

        return [
            'pendingVerifikasi' => $pendingVerifikasi,
            'pendingVerifikasiHasil' => $pendingVerifikasiHasil,
            'totalPending' => $pendingVerifikasi + $pendingVerifikasiHasil,
        ];
    }

    private function getAnalisData()
    {
        $userId = auth()->user()->user_id;

        // Forms with parameters assigned to this analyst (in progress)
        $myForms = FormPengujian::where('status', 'dalam_pengujian')
            ->whereHas('samples.sampleParameters', function($q) use ($userId) {
                $q->where('assigned_analyst_id', $userId);
            })
            ->count();

        // Completed forms with parameters assigned to this analyst
        $completedForms = FormPengujian::whereIn('status', ['verifikasi_hasil_divisi', 'input_lhp', 'ttd_upa', 'kirim_customer', 'selesai'])
            ->whereHas('samples.sampleParameters', function($q) use ($userId) {
                $q->where('assigned_analyst_id', $userId);
            })
            ->count();

        return [
            'myForms' => $myForms,
            'completedForms' => $completedForms,
        ];
    }

    /**
     * Get form list with filters for dashboard
     */
    private function getFormListData(Request $request)
    {
        $query = FormPengujian::with(['samples.sampleParameters.parameter', 'samples.sampleParameters.assignedAnalyst', 'admin', 'assignedAnalyst']);
        $user = auth()->user();
        $role = $user->role_id;

        // Tab-based filtering (takes precedence)
        $currentTab = $request->get('tab', 'aktif');
        if ($currentTab == 'selesai') {
            $query->whereIn('status', ['selesai', 'ditolak']);
        } else {
            // 'aktif' tab - exclude completed/rejected
            $query->whereNotIn('status', ['selesai', 'ditolak']);
        }

        // Check if any additional filter is applied
        $hasFilters = $request->filled('search') || $request->filled('status') || 
                      $request->filled('stage') || $request->filled('quick') ||
                      $request->filled('deadline_from') || $request->filled('deadline_to') ||
                      $request->filled('received_from') || $request->filled('received_to');

        // Apply role-based default filter when no filters are applied (only on aktif tab)
        $defaultFilter = null;
        if (!$hasFilters && $currentTab == 'aktif') {
            switch ($role) {
                case Role::KEPALA_UPA:
                    // Show forms waiting for UPA verification
                    $query->whereIn('status', ['verifikasi_upa_1', 'verifikasi_hasil_upa']);
                    $defaultFilter = 'kepala_upa';
                    break;
                case Role::KEPALA_DIVISI:
                    // Show forms waiting for Divisi verification
                    $query->whereIn('status', ['verifikasi_divisi', 'verifikasi_hasil_divisi']);
                    $defaultFilter = 'kepala_divisi';
                    break;
                case Role::ANALIS:
                    // Show forms in testing or assigned to this analyst
                    $query->where(function($q) use ($user) {
                        $q->where('status', 'dalam_pengujian')
                          ->where(function($q2) use ($user) {
                              $q2->whereNull('assigned_analyst_id')
                                 ->orWhere('assigned_analyst_id', $user->user_id);
                          });
                    });
                    $defaultFilter = 'analis';
                    break;
                // Admin sees all forms by default (no filter)
            }
        }

        // Search filter
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

        // Stage filter
        if ($request->filled('stage')) {
            $query->where('status', $request->stage);
        }

        // Deadline range
        if ($request->filled('deadline_from')) {
            $query->where('deadline_date', '>=', $request->deadline_from);
        }
        if ($request->filled('deadline_to')) {
            $query->where('deadline_date', '<=', $request->deadline_to);
        }

        // Received date range
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

        $forms = $query->paginate(10)->withQueryString();

        // Status counts
        $formStatusCounts = [
            'total' => FormPengujian::count(),
            'active' => FormPengujian::whereNotIn('status', ['selesai', 'ditolak'])->count(),
            'selesai' => FormPengujian::where('status', 'selesai')->count(),
            'overdue' => FormPengujian::whereNotIn('status', ['selesai', 'ditolak'])
                            ->where('deadline_date', '>=', Carbon::today())
                            ->where('deadline_date', '<=', Carbon::today()->addDays(3))->count(),
        ];

        $statusOptions = [
            'verifikasi_upa_1' => 'Verifikasi UPA',
            'verifikasi_divisi' => 'Verifikasi Divisi',
            'dalam_pengujian' => 'Dalam Pengujian',
            'verifikasi_hasil_divisi' => 'Ver. Hasil Divisi',
            'input_lhp' => 'Input LHP',
            'ttd_divisi_lhp' => 'TTD Divisi (LHP)',
            'ttd_upa' => 'Menunggu TTD UPA',
            'kirim_customer' => 'Kirim Ke Customer',
            'selesai' => 'Selesai',
            'ditolak' => 'Ditolak',
        ];

        return [
            'forms' => $forms,
            'formStatusCounts' => $formStatusCounts,
            'statusOptions' => $statusOptions,
            'defaultFilter' => $defaultFilter,
        ];
    }
}
