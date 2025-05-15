<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Leave;
use App\Models\User;
use Illuminate\Http\Request;
class DashboardController extends Controller
{
    public function countOnHoldLeavesThisMonth()
    {
        $count = Leave::where('status', 'on_hold')
            ->whereMonth('start_date', now()->month)
            ->whereYear('start_date', now()->year)
            ->count();

        return response()->json(['on_hold_this_month' => $count]);
    }

    public function countRejectedLeavesThisMonth()
    {
        $count = Leave::where('status', 'rejected')
            ->whereMonth('start_date', now()->month)
            ->whereYear('start_date', now()->year)
            ->count();

        return response()->json(['rejected_this_month' => $count]);
    }

    public function countApprovedLeavesThisMonth()
    {
        $count = Leave::where('status', 'approved')
            ->whereMonth('start_date', now()->month)
            ->whereYear('start_date', now()->year)
            ->count();

        return response()->json(['approved_this_month' => $count]);
    }

    public function getLeavesToday()
    {
        $today = now()->toDateString();

        $leavesToday = Leave::whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->where('status', 'approved')
            ->with('user:id,first_name,last_name')
            ->get()
            ->map(function ($leave) {
                return [
                    'full_name' => $leave->user->first_name . ' ' . $leave->user->last_name,
                    'leave_type' => $leave->leave_type,
                ];
            });

        return response()->json($leavesToday);
    }

    //Diagramme en cercle : Montrer la distribution des différents types de congés demandés de cette moins actuel de cette annnee par les employés
    public function leaveTypeDistribution(Request $request)
    {
        $mr = $request->input('mr');

        if (!$mr || !preg_match('/^\d{2}-\d{4}$/', $mr)) {
            return response()->json([
                'error' => 'The mr field is required and must be in format MM-YYYY.'
            ], 422);
        }

        [$month, $year] = explode('-', $mr);

        $leaveCounts = Leave::select('leave_type')
            ->selectRaw('COUNT(*) as count')
            ->where('status', 'approved')
            ->whereMonth('start_date', $month)
            ->whereYear('start_date', $year)
            ->groupBy('leave_type')
            ->get();

        $totalLeaves = Leave::where('status', 'approved')
            ->whereMonth('start_date', $month)
            ->whereYear('start_date', $year)
            ->count();

        $leavePercentages = $leaveCounts->map(function ($item) use ($totalLeaves) {
            $percentage = ($item->count / max($totalLeaves, 1)) * 100;
            return [
                'leave_type' => $item->leave_type,
                'percentage' => round($percentage, 2),
            ];
        });

        return response()->json([
            'leave_distribution' => $leavePercentages,
        ]);
    }



    // count de demandes de congés (en attente, approuvées, rejetées) pour cette moins actuel de cette annee  : Diagramme en cercle
    public function leaveStatusDistribution(Request $request)
    {
        $mr = $request->input('mr');

        if (!$mr || !preg_match('/^\d{2}-\d{4}$/', $mr)) {
            return response()->json([
                'error' => 'The mr field is required and must be in format MM-YYYY.'
            ], 422);
        }

        [$month, $year] = explode('-', $mr);

        $statusCounts = Leave::select('status')
            ->selectRaw('COUNT(*) as count')
            ->whereMonth('start_date', $month)
            ->whereYear('start_date', $year)
            ->groupBy('status')
            ->get();

        return response()->json([
            'leave_status_distribution' => $statusCounts,
        ]);
    }



    //Identifier les employés qui prennent le plus de congés. : Diagramme en barres ( ken user 3atah year ta3tik year eli 5taro user snn par defaut taffichi bel year actuel )
    public function approvedLeavesByEmployee()
    {
        try {
            $year = request()->input('year', now()->year);

            $approvedLeaves = Leave::where('status', 'approved')
                ->whereYear('start_date', $year)
                ->select('user_id')
                ->selectRaw('SUM(effective_leave_days) as total_days')
                ->groupBy('user_id')
                ->orderByDesc('total_days')
                ->take(10)
                ->get();

            $approvedLeavesWithNames = $approvedLeaves->map(function ($item) {
                $user = User::find($item->user_id);
                $fullName = $user ? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) : 'Unknown';
                return [
                    'user_id' => $item->user_id,
                    'name' => $fullName,
                    'total_days' => $item->total_days,
                ];
            });

            return response()->json([
                'year' => $year,
                'approved_leaves_by_employee' => $approvedLeavesWithNames,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Une erreur est survenue.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }


    //Comparer les tendances des demandes de congés qui approved entre différentes années. : Graphe linéaire avec plusieurs courbes
    public function compareApprovedLeavesByYear()
    {
        $currentYear = now()->year;
        $years = [
            $currentYear - 1, // Année passée
            $currentYear,     // Année en cours
            $currentYear + 1, // Année future
        ];

        $leaveData = [];

        foreach ($years as $year) {
            $monthlyData = Leave::where('status', 'approved')
                ->whereYear('start_date', $year)
                ->selectRaw('MONTH(start_date) as month, COUNT(*) as count')
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
