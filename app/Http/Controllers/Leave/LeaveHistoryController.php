<?php

namespace App\Http\Controllers\Leave;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Leave;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class LeaveHistoryController extends Controller
{
    public function index(Request $request)
    {
        $userId = auth()->id();
        $minYear = User::where('id', $userId)->value(DB::raw('YEAR(start_date)'));
        $year = $request->input('year', null);
        $maxYear = Carbon::now()->year + 1;
        $availableYears = range($minYear, $maxYear);
        $query = Leave::where('user_id', $userId);
        if ($year) {
            $query->whereYear('start_date', $year);
        }
        $leaves = $query->orderBy('start_date', 'desc')
                        ->paginate(6);
        $totalLeaveDays = $leaves->sum(function($leave) {
            return ($leave->reason === 'sick_leave') 
                ? $leave->effective_leave_days
                : $leave->leave_days_requested;
        });
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
            'data' => $data,
            'meta' => [
                'total_leave_days' => $totalLeaveDays,
                'available_years' => $availableYears,
                'selected_year' => $year,
                'current_page' => $leaves->currentPage(),
                'per_page' => $leaves->perPage(),
                'total_pages' => $leaves->lastPage(),
                'total_leaves' => $leaves->total(),
            ],
        ]);
    }
}
