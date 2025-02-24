<?php

namespace App\Http\Controllers\Leave;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Leave;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Exception;

class ViewLeaveController extends Controller
{
    public function showLeaves(Request $request, $userId)
    {
        try {
            $user = User::findOrFail($userId);
            $authUser = Auth::user();
            $year = $request->input('year', null);

            $minYear = Carbon::parse($user->start_date)->year;
            $maxYear = Carbon::now()->year + 1;
            $availableYears = range($minYear, $maxYear);

            $totalLeaveDaysQuery = Leave::where('user_id', $userId);
            if ($year) {
                $totalLeaveDaysQuery->whereYear('start_date', $year);
            }
    
            $totalLeaveDays = $totalLeaveDaysQuery->sum(DB::raw("
                CASE
                    WHEN reason = 'sick_leave' THEN effective_leave_days
                    ELSE leave_days_requested
                END
            "));
                $leavesQuery = Leave::where('user_id', $userId)
                ->select('id', 'start_date', 'end_date', 'reason', 'other_reason', 'leave_days_requested', 'effective_leave_days', 'attachment_path', 'status');
    
            if ($year) {
                $leavesQuery->whereYear('start_date', $year);
            }
    
            $leaves = $leavesQuery->orderBy('start_date', 'desc')
                                  ->paginate(6)
                                  ->appends($request->query());

            $data = $leaves->map(function ($leave) {
                $leaveData = [
                    'id'=> $leave->id,
                    'start_date' => $leave->start_date,
                    'end_date' => $leave->end_date,
                    'reason' => $leave->reason,
                    'status' => $leave->status,
                    'leave_days_requested' => $leave->leave_days_requested,
                ];

                if ($leave->attachment_path) {
                    $leaveData['attachment_path'] = asset($leave->attachment_path);
                }

                if ($leave->reason === 'other') {
                    $leaveData['other_reason'] = $leave->other_reason;
                    unset($leaveData['reason']);
                } elseif ($leave->reason === 'sick_leave') {
                    $leaveData['effective_leave_days'] = $leave->effective_leave_days;
                    unset($leaveData['leave_days_requested']);
                    unset($leaveData['other_reason']);
                }

                return $leaveData;
            });

            $response = [
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
            ];

            if (in_array($authUser->role, ['admin', 'hr'])) {
                $response['full_name'] = "{$user->first_name} {$user->last_name}";
            }

            return response()->json($response);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'An error occurred while retrieving leave data.',
                'message' => $e->getMessage()
            ], 500);
        }
    }


    public function updateStatus(Request $request, $leaveId)
    {
        try {
            $validated = $request->validate([
                'status' => 'required|in:approved,rejected',
            ]);

            $leave = Leave::findOrFail($leaveId);
            $leave->status = $validated['status'];
            $leave->save();

            return response()->json(['message' => 'Leave status updated successfully!']);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'An error occurred while updating leave status.',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function updateLeave(Request $request, $leaveId)
    {
        try {
            $authUser = Auth::user();
            $leave = Leave::findOrFail($leaveId);
    
            if ($leave->status !== 'on_hold') {
                return response()->json(['error' => 'Only pending leaves can be modified.'], 403);
            }
    
            $validated = $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'reason' => 'required|in:vacation,travel_leave,paternity_leave,maternity_leave,sick_leave,other',
                'other_reason' => 'nullable|required_if:reason,other|string|max:255',
                'leave_days_requested' => 'required|numeric|min:1',
                'attachment' => 'nullable|file|mimes:pdf,jpg,png|max:2048|required_if:reason,sick_leave'
            ], [
                'other_reason.required_if' => 'The "Other reason" field is required if the leave type is "other".',
                'attachment.required_if' => 'An attachment is required for sick leave.'

            ]);
            $leave->start_date = $validated['start_date'];
            $leave->end_date = $validated['end_date'];
            $leave->reason = $validated['reason'];
            $leave->leave_days_requested = $validated['leave_days_requested'];
    
            // Gérer le cas "other"
            $leave->other_reason = $validated['reason'] === 'other' ? $validated['other_reason'] : null;
                if ($validated['reason'] === 'sick_leave') {
                $leave->effective_leave_days = max(0, $validated['leave_days_requested'] - 2);
            } else {
                $leave->effective_leave_days = 0;
            }
            if ($request->hasFile('attachment')) {
                if ($leave->attachment_path) {
                    Storage::disk('public')->delete(str_replace(env('STORAGE').'/attachments/', '', $leave->attachment_path));
                }
                $file = $request->file('attachment');
                $path = $file->store('attachments', 'public');
                $filename = basename($path);
                $leave->attachment_path = env('STORAGE').'/attachments/'.$filename;
            }
    
            $leave->save();
    
            return response()->json(['message' => 'Leave updated successfully!']);
    
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Error updating leave.',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    
    public function deleteLeave($leaveId)
    {
        try {
            $leave = Leave::findOrFail($leaveId);

            if ($leave->status !== 'on_hold') {
                return response()->json(['error' => 'Only pending leaves can be deleted.'], 403);
            }

            if ($leave->attachment_path && Storage::disk('public')->exists($leave->attachment_path)) {
                Storage::disk('public')->delete($leave->attachment_path);
            }

            $leave->delete();

            return response()->json(['message' => 'Leave deleted successfully!']);
        } catch (Exception $e) {
            return response()->json(['error' => 'Error deleting leave.', 'message' => $e->getMessage()], 500);
        }
    }

    
}
