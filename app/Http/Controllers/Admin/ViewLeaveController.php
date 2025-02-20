<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Leave;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ViewLeaveController extends Controller
{
    
    public function showLeaves(Request $request, $userId)
    {
        $user = User::findOrFail($userId);
        $year = $request->input('year');
        $minYear = Leave::where('user_id', $userId)->min(DB::raw('YEAR(start_date)'));
        $maxYear = Carbon::now()->year + 1;
        $availableYears = range($minYear, $maxYear);
        $leavesQuery = Leave::where('user_id', $userId)
        ->select('id', 'start_date', 'end_date', 'reason', 'other_reason', 'leave_days_requested', 'effective_leave_days', 'status');
    
        if ($year) {
            $leavesQuery->whereYear('start_date', $year);
        }
    
        $leaves = $leavesQuery->paginate(10);
            $totalLeaveDaysQuery = Leave::where('user_id', $userId)
            ->selectRaw("SUM(leave_days_requested + IF(reason = 'sick', effective_leave_days, 0)) AS total_leave_days");
    
        if ($year) {
            $totalLeaveDaysQuery->whereYear('start_date', $year);
        }
    
        $totalLeaveDays = $totalLeaveDaysQuery->value('total_leave_days') ?? 0;
    
        return response()->json([
            'full_name' => "{$user->first_name} {$user->last_name}",
            'available_years' => $availableYears,
            'total_leave_days' => $totalLeaveDays,
            'data' => $leaves->items(),
            'meta' => [
                'current_page' => $leaves->currentPage(),
                'per_page' => $leaves->perPage(),
                'total_pages' => $leaves->lastPage(),
                'total_leaves' => $leaves->total(),
            ],
        ]);
    }
    

    public function updateStatus(Request $request, $leaveId)
    {
        $validated = $request->validate([
            'status' => 'required|in:accepted,rejected,on hold',
        ]);

        $leave = Leave::findOrFail($leaveId);
        $leave->status = $validated['status'];
        $leave->save();

        return response()->json(['message' => 'Leave status updated successfully!']);
    }
}
