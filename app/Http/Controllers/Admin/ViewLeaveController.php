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
    $year = $request->input('year', null);

    $minYear = Carbon::parse($user->start_date)->year;
    $maxYear = Carbon::now()->year + 1;
    $availableYears = range($minYear, $maxYear);

    $leavesQuery = Leave::where('user_id', $userId)
        ->select('id', 'start_date', 'end_date', 'reason', 'other_reason', 'leave_days_requested', 'effective_leave_days', 'attachment_path', 'status');

    if ($year) {
        $leavesQuery->whereYear('start_date', $year);
    }

    $leaves = $leavesQuery->orderBy('start_date', 'desc')
                          ->paginate(10);

    $totalLeaveDaysQuery = $leaves->sum(function($leave) {
        return ($leave->reason === 'sick_leave') 
            ? $leave->effective_leave_days
            : $leave->leave_days_requested;
    });

    $totalLeaveDays = $totalLeaveDaysQuery ?? 0;

    $data = $leaves->map(function($leave) {
        $leaveData = [
            'start_date' => $leave->start_date,
            'end_date' => $leave->end_date,
            'reason' => $leave->reason,
            'status' => $leave->status,
        ];

        if ($leave->attachment_path) {
            $leaveData['attachment'] = asset($leave->attachment_path);
        }

        if ($leave->reason === 'other') {
            $leaveData['other_reason'] = $leave->other_reason;
            unset($leaveData['reason']);
        } elseif ($leave->reason === 'sick_leave') {
            $leaveData['effective_leave_days'] = $leave->effective_leave_days;
            unset($leaveData['leave_days_requested']);
            unset($leaveData['other_reason']);
        } else {
            $leaveData['leave_days_requested'] = $leave->leave_days_requested;
        }

        return $leaveData;
    });

    return response()->json([
        'full_name' => "{$user->first_name} {$user->last_name}",
        'available_years' => $availableYears,
        'total_leave_days' => $totalLeaveDays,
        'data' => $data,
        'meta' => [
            'selected_year' => $year,
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
