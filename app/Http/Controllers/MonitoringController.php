<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Sp3Document;
use App\Models\AnalysisResult;
use App\Enums\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class MonitoringController extends Controller
{
    public function index(Request $request)
    {
        $currentYear   = now()->year;
        $selectedYear  = (int) $request->get('year', $currentYear);
        $selectedMonth = $request->get('month') ? (int) $request->get('month') : null;
        $selectedAnalyst = $request->get('analyst_id') ? (int) $request->get('analyst_id') : null;

        $earliestDate = Sp3Document::min('created_at');
        $earliestYear = $earliestDate ? Carbon::parse($earliestDate)->year : $currentYear;
        $years = range($currentYear, max((int)$earliestYear, $currentYear - 5));

        $analysts = User::where('role_id', Role::ANALIS)
            ->where('is_active', true)
            ->orderBy('full_name')
            ->get();

        // --- Tab 1: Per-analyst summary cards ---
        $analystStats = $analysts->map(function (User $analyst) use ($selectedYear, $selectedMonth) {
            $id = $analyst->user_id;

            $base = Sp3Document::where('assigned_analyst_id', $id)
                ->whereYear('created_at', $selectedYear)
                ->when($selectedMonth, fn($q) => $q->whereMonth('created_at', $selectedMonth));

            $totalAssigned  = (clone $base)->count();
            $viewed         = (clone $base)->whereNotNull('first_viewed_at')->count();
            $notViewed      = $totalAssigned - $viewed;

            $activeAssigned = (clone $base)
                ->whereHas('form', fn($q) => $q->where('status', 'dalam_pengujian'))
                ->count();

            $overdueAssigned = (clone $base)
                ->whereHas('form', fn($q) => $q
                    ->where('status', 'dalam_pengujian')
                    ->where('deadline_date', '<', Carbon::today())
                )->count();

            $resultsBase = AnalysisResult::where('analyst_id', $id)
                ->whereYear('analysis_date', $selectedYear)
                ->when($selectedMonth, fn($q) => $q->whereMonth('analysis_date', $selectedMonth));

            $completedResults = (clone $resultsBase)->count();

            $parametersWorked = (clone $resultsBase)
                ->join('sample_parameters', 'analysis_results.sample_parameter_id', '=', 'sample_parameters.id')
                ->distinct('sample_parameters.parameter_id')
                ->count('sample_parameters.parameter_id');

            $sp3Details = Sp3Document::where('assigned_analyst_id', $id)
                ->with(['form', 'parameter'])
                ->whereYear('created_at', $selectedYear)
                ->when($selectedMonth, fn($q) => $q->whereMonth('created_at', $selectedMonth))
                ->latest()
                ->get();

            return [
                'analyst'          => $analyst,
                'total_assigned'   => $totalAssigned,
                'active_assigned'  => $activeAssigned,
                'viewed'           => $viewed,
                'not_viewed'       => $notViewed,
                'completed'        => $completedResults,
                'overdue'          => $overdueAssigned,
                'parameters_count' => $parametersWorked,
                'sp3_details'      => $sp3Details,
            ];
        })->sortByDesc('active_assigned')->values();

        // --- Tab 2: Assignment log ---
        $logQuery = Sp3Document::whereNotNull('assigned_analyst_id')
            ->with(['assignedAnalyst', 'parameter', 'form'])
            ->whereYear('assigned_at', $selectedYear)
            ->when($selectedMonth, fn($q) => $q->whereMonth('assigned_at', $selectedMonth))
            ->when($selectedAnalyst, fn($q) => $q->where('assigned_analyst_id', $selectedAnalyst))
            ->orderByDesc('assigned_at');

        $log = $logQuery->paginate(30)->withQueryString();

        // Compute time metrics per log row
        $log->through(function (Sp3Document $sp3) {
            // Earliest result submitted for this SP3's parameter
            $sampleIds = $sp3->form
                ? $sp3->form->samples()->pluck('samples.id')
                : collect();

            $firstResult = AnalysisResult::whereHas('sampleParameter', fn($q) =>
                    $q->whereIn('sample_id', $sampleIds)
                      ->where('parameter_id', $sp3->parameter_id)
                )->orderBy('created_at')->first();

            $sp3->result_submitted_at = $firstResult?->created_at;

            $sp3->response_time  = $sp3->assigned_at && $sp3->first_viewed_at
                ? $sp3->assigned_at->diffForHumans($sp3->first_viewed_at, true)
                : null;

            $sp3->work_duration  = $sp3->first_viewed_at && $sp3->result_submitted_at
                ? $sp3->first_viewed_at->diffForHumans($sp3->result_submitted_at, true)
                : null;

            $sp3->turnaround     = $sp3->assigned_at && $sp3->result_submitted_at
                ? $sp3->assigned_at->diffForHumans($sp3->result_submitted_at, true)
                : null;

            return $sp3;
        });

        // --- Overall KPIs ---
        $totalAssignedAll = Sp3Document::whereNotNull('assigned_analyst_id')
            ->whereYear('assigned_at', $selectedYear)
            ->when($selectedMonth, fn($q) => $q->whereMonth('assigned_at', $selectedMonth))
            ->count();

        $totalUnassigned = Sp3Document::whereNull('assigned_analyst_id')
            ->whereHas('form', fn($q) => $q->where('status', 'dalam_pengujian'))
            ->count();

        $busiestStat = $analystStats->first();

        $overallStats = [
            'total_analysts'   => $analysts->count(),
            'total_assigned'   => $totalAssignedAll,
            'total_unassigned' => $totalUnassigned,
            'busiest_name'     => $busiestStat && $busiestStat['active_assigned'] > 0
                                    ? $busiestStat['analyst']->full_name : '—',
            'busiest_count'    => $busiestStat ? $busiestStat['active_assigned'] : 0,
        ];

        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        return view('admin.monitoring', compact(
            'analystStats', 'overallStats', 'log',
            'analysts', 'selectedYear', 'selectedMonth', 'selectedAnalyst',
            'years', 'months'
        ));
    }
}
