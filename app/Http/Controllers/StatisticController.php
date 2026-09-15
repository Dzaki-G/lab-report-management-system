<?php

namespace App\Http\Controllers;

use App\Models\FormPengujian;
use App\Models\SampleParameter;
use App\Models\Parameter;
use App\Models\User;
use App\Enums\Role;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StatisticController extends Controller
{
    /**
     * Display statistics charts and summaries
     * Accessible by Admin, Kepala UPA, and Kepala Divisi
     */
    public function index(Request $request)
    {
        $currentYear = date('Y');
        $selectedYear = $request->get('year', $currentYear);
        $selectedMonth = $request->get('month', 'all');

        $isSqlite = DB::getDriverName() === 'sqlite';

        // Get available years for filtering
        if ($isSqlite) {
            $availableYears = FormPengujian::selectRaw("strftime('%Y', received_date) as year")
                ->distinct()
                ->orderBy('year', 'desc')
                ->pluck('year')
                ->toArray();
        } else {
            $availableYears = FormPengujian::selectRaw('YEAR(received_date) as year')
                ->distinct()
                ->orderBy('year', 'desc')
                ->pluck('year')
                ->toArray();
        }

        if (empty($availableYears)) {
            $availableYears = [$currentYear];
        }

        // Month names for filter dropdown
        $monthNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        // Period filter helper
        $applyPeriodFilter = function($query, $dateColumn = 'received_date') use ($selectedYear, $selectedMonth, $isSqlite) {
            if ($isSqlite) {
                $query->whereRaw("strftime('%Y', $dateColumn) = ?", [$selectedYear]);
                if ($selectedMonth !== 'all') {
                    $query->whereRaw("cast(strftime('%m', $dateColumn) as integer) = ?", [$selectedMonth]);
                }
            } else {
                $query->whereYear($dateColumn, $selectedYear);
                if ($selectedMonth !== 'all') {
                    $query->whereMonth($dateColumn, $selectedMonth);
                }
            }
            return $query;
        };

        // 1. Volume Chart (Monthly vs Daily)
        $volumeChartType = ($selectedMonth === 'all') ? 'monthly' : 'daily';
        $volumeLabels = [];
        $volumeFormValues = [];
        $volumeSampleValues = [];

        if ($volumeChartType === 'monthly') {
            // Volume bulanan
            if ($isSqlite) {
                $monthlyForms = FormPengujian::whereRaw("strftime('%Y', received_date) = ?", [$selectedYear])
                    ->selectRaw("cast(strftime('%m', received_date) as integer) as month, count(*) as count")
                    ->groupBy('month')
                    ->pluck('count', 'month')
                    ->toArray();

                $monthlySamples = DB::table('samples')
                    ->join('form_pengujian', 'samples.form_pengujian_id', '=', 'form_pengujian.id')
                    ->whereRaw("strftime('%Y', form_pengujian.received_date) = ?", [$selectedYear])
                    ->selectRaw("cast(strftime('%m', form_pengujian.received_date) as integer) as month, count(*) as count")
                    ->groupBy('month')
                    ->pluck('count', 'month')
                    ->toArray();
            } else {
                $monthlyForms = FormPengujian::whereYear('received_date', $selectedYear)
                    ->selectRaw('MONTH(received_date) as month, count(*) as count')
                    ->groupBy('month')
                    ->pluck('count', 'month')
                    ->toArray();

                $monthlySamples = DB::table('samples')
                    ->join('form_pengujian', 'samples.form_pengujian_id', '=', 'form_pengujian.id')
                    ->whereYear('form_pengujian.received_date', $selectedYear)
                    ->selectRaw('MONTH(form_pengujian.received_date) as month, count(*) as count')
                    ->groupBy('month')
                    ->pluck('count', 'month')
                    ->toArray();
            }

            $shortMonths = [
                1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun',
                7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
            ];

            for ($i = 1; $i <= 12; $i++) {
                $volumeLabels[] = $shortMonths[$i];
                $volumeFormValues[] = $monthlyForms[$i] ?? 0;
                $volumeSampleValues[] = $monthlySamples[$i] ?? 0;
            }
        } else {
            // Volume harian
            $daysInMonth = Carbon::createFromDate($selectedYear, $selectedMonth, 1)->daysInMonth;

            if ($isSqlite) {
                $dailyForms = FormPengujian::whereRaw("strftime('%Y', received_date) = ? AND cast(strftime('%m', received_date) as integer) = ?", [$selectedYear, $selectedMonth])
                    ->selectRaw("cast(strftime('%d', received_date) as integer) as day, count(*) as count")
                    ->groupBy('day')
                    ->pluck('count', 'day')
                    ->toArray();

                $dailySamples = DB::table('samples')
                    ->join('form_pengujian', 'samples.form_pengujian_id', '=', 'form_pengujian.id')
                    ->whereRaw("strftime('%Y', form_pengujian.received_date) = ? AND cast(strftime('%m', form_pengujian.received_date) as integer) = ?", [$selectedYear, $selectedMonth])
                    ->selectRaw("cast(strftime('%d', form_pengujian.received_date) as integer) as day, count(*) as count")
                    ->groupBy('day')
                    ->pluck('count', 'day')
                    ->toArray();
            } else {
                $dailyForms = FormPengujian::whereYear('received_date', $selectedYear)
                    ->whereMonth('received_date', $selectedMonth)
                    ->selectRaw('DAY(received_date) as day, count(*) as count')
                    ->groupBy('day')
                    ->pluck('count', 'day')
                    ->toArray();

                $dailySamples = DB::table('samples')
                    ->join('form_pengujian', 'samples.form_pengujian_id', '=', 'form_pengujian.id')
                    ->whereYear('form_pengujian.received_date', $selectedYear)
                    ->whereMonth('form_pengujian.received_date', $selectedMonth)
                    ->selectRaw('DAY(form_pengujian.received_date) as day, count(*) as count')
                    ->groupBy('day')
                    ->pluck('count', 'day')
                    ->toArray();
            }

            for ($i = 1; $i <= $daysInMonth; $i++) {
                $volumeLabels[] = $i;
                $volumeFormValues[] = $dailyForms[$i] ?? 0;
                $volumeSampleValues[] = $dailySamples[$i] ?? 0;
            }
        }

        // 2. Yearly form volume (last 5 years)
        if ($isSqlite) {
            $yearlyForms = FormPengujian::selectRaw("strftime('%Y', received_date) as year, count(*) as count")
                ->groupBy('year')
                ->orderBy('year', 'asc')
                ->take(5)
                ->pluck('count', 'year')
                ->toArray();
        } else {
            $yearlyForms = FormPengujian::selectRaw('YEAR(received_date) as year, count(*) as count')
                ->groupBy('year')
                ->orderBy('year', 'asc')
                ->take(5)
                ->pluck('count', 'year')
                ->toArray();
        }

        // 3. Status distribution (filtered)
        $statusQuery = FormPengujian::selectRaw('status, count(*) as count')->groupBy('status');
        $statusQuery = $applyPeriodFilter($statusQuery, 'received_date');
        $rawStatusStats = $statusQuery->get();

        $statusStats = [];
        $statusColors = [];
        $statusLabels = [
            'verifikasi_upa_1' => 'Verifikasi UPA',
            'verifikasi_divisi' => 'Verifikasi Divisi',
            'dalam_pengujian' => 'Dalam Pengujian',
            'verifikasi_hasil_divisi' => 'Ver. Hasil Divisi',
            'input_lhp' => 'Input LHP',
            'ttd_divisi_lhp' => 'TTD Divisi (LHP)',
            'ttd_upa' => 'TTD UPA (LHP)',
            'kirim_customer' => 'Kirim Customer',
            'selesai' => 'Selesai',
            'ditolak' => 'Ditolak',
        ];
        $statusColorMap = [
            'verifikasi_upa_1' => '#3b82f6',
            'verifikasi_divisi' => '#6366f1',
            'dalam_pengujian' => '#f59e0b',
            'verifikasi_hasil_divisi' => '#8b5cf6',
            'input_lhp' => '#ec4899',
            'ttd_divisi_lhp' => '#14b8a6',
            'ttd_upa' => '#06b6d4',
            'kirim_customer' => '#059669',
            'selesai' => '#10b981',
            'ditolak' => '#ef4444',
        ];
        foreach ($rawStatusStats as $stat) {
            $label = $statusLabels[$stat->status] ?? $stat->status;
            $statusStats[$label] = $stat->count;
            $statusColors[] = $statusColorMap[$stat->status] ?? '#9ca3af';
        }

        // 4. Top 10 parameters (filtered)
        $paramQuery = SampleParameter::join('parameters', 'sample_parameters.parameter_id', '=', 'parameters.id')
            ->join('samples', 'sample_parameters.sample_id', '=', 'samples.id')
            ->join('form_pengujian', 'samples.form_pengujian_id', '=', 'form_pengujian.id')
            ->selectRaw('parameters.name, count(*) as count')
            ->groupBy('parameters.id', 'parameters.name')
            ->orderBy('count', 'desc')
            ->take(10);
        $paramQuery = $applyPeriodFilter($paramQuery, 'form_pengujian.received_date');
        $parameterStats = $paramQuery->pluck('count', 'parameters.name')->toArray();

        // 5. Analyst workload (filtered)
        $analysts = User::where('role_id', Role::ANALIS)->get();
        $analystWorkload = [];
        foreach ($analysts as $analyst) {
            $activeQuery = SampleParameter::join('samples', 'sample_parameters.sample_id', '=', 'samples.id')
                ->join('form_pengujian', 'samples.form_pengujian_id', '=', 'form_pengujian.id')
                ->where('sample_parameters.filled_by_analyst_id', $analyst->user_id)
                ->whereIn('sample_parameters.status', ['pending', 'in_progress']);
            $activeQuery = $applyPeriodFilter($activeQuery, 'form_pengujian.received_date');
            $activeCount = $activeQuery->count();

            $completedQuery = SampleParameter::join('samples', 'sample_parameters.sample_id', '=', 'samples.id')
                ->join('form_pengujian', 'samples.form_pengujian_id', '=', 'form_pengujian.id')
                ->where('sample_parameters.filled_by_analyst_id', $analyst->user_id)
                ->where('sample_parameters.status', 'done');
            $completedQuery = $applyPeriodFilter($completedQuery, 'form_pengujian.received_date');
            $completedCount = $completedQuery->count();

            if ($activeCount > 0 || $completedCount > 0) {
                $analystWorkload[$analyst->full_name] = [
                    'active' => $activeCount,
                    'completed' => $completedCount
                ];
            }
        }

        // 6. General KPI counts (filtered)
        $totalFormsQuery = FormPengujian::query();
        $totalFormsQuery = $applyPeriodFilter($totalFormsQuery, 'received_date');
        $totalForms = $totalFormsQuery->count();

        $activeFormsQuery = FormPengujian::whereNotIn('status', ['selesai', 'ditolak']);
        $activeFormsQuery = $applyPeriodFilter($activeFormsQuery, 'received_date');
        $activeForms = $activeFormsQuery->count();

        $completedFormsQuery = FormPengujian::where('status', 'selesai');
        $completedFormsQuery = $applyPeriodFilter($completedFormsQuery, 'received_date');
        $completedForms = $completedFormsQuery->count();

        $totalSamplesQuery = DB::table('samples')->join('form_pengujian', 'samples.form_pengujian_id', '=', 'form_pengujian.id');
        $totalSamplesQuery = $applyPeriodFilter($totalSamplesQuery, 'form_pengujian.received_date');
        $totalSamples = $totalSamplesQuery->count();

        $kpiCounts = [
            'total_forms' => $totalForms,
            'active_forms' => $activeForms,
            'completed_forms' => $completedForms,
            'total_samples' => $totalSamples,
        ];

        return view('statistics.index', compact(
            'availableYears',
            'selectedYear',
            'selectedMonth',
            'monthNames',
            'volumeChartType',
            'volumeLabels',
            'volumeFormValues',
            'volumeSampleValues',
            'yearlyForms',
            'statusStats',
            'statusColors',
            'parameterStats',
            'analystWorkload',
            'kpiCounts'
        ));
    }
}
