<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Leave;
class DashboardController extends Controller
{
    //Diagramme en cercle : Montrer la distribution des différents types de congés demandés de cette moins actuel  par les employés
    public function leaveTypeDistribution()
    {
        $currentMonth = now()->month;
        $currentYear = now()->year;
        $leaveCounts = Leave::select('leave_type')
            ->selectRaw('COUNT(*) as count')
            ->where('status', 'approved')
            ->whereMonth('start_date', $currentMonth)
            ->whereYear('start_date', $currentYear)
            ->groupBy('leave_type')
            ->get();

        $totalLeaves = Leave::where('status', 'approved')
            ->whereMonth('start_date', $currentMonth)
            ->whereYear('start_date', $currentYear)
            ->count();
        $leavePercentages = $leaveCounts->map(function ($item) use ($totalLeaves) {
            $percentage = ($item->count / $totalLeaves) * 100;
            return [
                'leave_type' => $item->leave_type,
                'percentage' => round($percentage, 2),
            ];
        });
        return response()->json([
            'leave_distribution' => $leavePercentages,
        ]);
    }

    // count de demandes de congés (en attente, approuvées, rejetées) pour cette moins actuel. : Diagramme en cercle
    public function leaveStatusDistribution()
    {
        $currentMonth = now()->month;
        $currentYear = now()->year;
        $statusCounts = Leave::select('status')
            ->selectRaw('COUNT(*) as count')
            ->whereMonth('start_date', $currentMonth)
            ->whereYear('start_date', $currentYear)
            ->groupBy('status')
            ->get();

        return response()->json([
            'leave_status_distribution' => $statusCounts,
        ]);
    }

    //Identifier les employés qui prennent le plus de congés. : Diagramme en barres
    public function approvedLeavesByEmployee()
    {
        $approvedLeaves = Leave::where('status', 'approved')
            ->select('user_id')
            ->selectRaw('SUM(effective_leave_days) as total_days')
            ->groupBy('user_id')
            ->orderByDesc('total_days')
            ->get();

        $approvedLeavesWithNames = $approvedLeaves->map(function ($item) {
            $user = \App\Models\User::find($item->user_id);
            $fullName = $user ? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) : 'Unknown';
            return [
                'user_id' => $item->user_id,
                'name' => $fullName,
                'total_days' => $item->total_days,
            ];
        });
        return response()->json([
            'approved_leaves_by_employee' => $approvedLeavesWithNames,
        ]);
    }

    //Comparer les tendances des demandes de congés qui approved entre différentes années. : Graphe linéaire avec plusieurs courbes
    public function compareApprovedLeavesByYear()
    {
        $years = Leave::where('status', 'approved')
            ->selectRaw('YEAR(start_date) as year')
            ->distinct()
            ->orderBy('year', 'asc')
            ->pluck('year');

        $leaveData = [];
        foreach ($years as $year) {
            $monthlyData = Leave::where('status', 'approved')
                ->whereYear('start_date', $year)
                ->selectRaw('MONTH(start_date) as month')
                ->selectRaw('COUNT(*) as count')
                ->groupBy('month')
                ->orderBy('month', 'asc')
                ->get()
                ->keyBy('month');
            $monthlyCounts = [];
            for ($month = 1; $month <= 12; $month++) {
                $monthlyCounts[$month] = $monthlyData->has($month) ? $monthlyData[$month]['count'] : 0;
            }

            $leaveData[] = [
                'year' => $year,
                'monthly_counts' => $monthlyCounts,
                'total_count' => array_sum($monthlyCounts),
            ];
        }
        return response()->json([
            'approved_leave_comparison_by_year' => $leaveData,
        ]);
    }
}
