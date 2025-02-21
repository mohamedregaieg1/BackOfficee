<?php

namespace App\Http\Controllers\Leave;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Leave;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
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
                    $leaveData['attachment'] = asset($leave->attachment_path);
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
                'reason' => 'required|string',
                'other_reason' => 'nullable|string',
                'leave_days_requested' => 'required|integer|min:1',
                'attachment' => 'nullable|file|mimes:pdf,jpg,png|max:2048'
            ]);

            $leave->update($validated);

            if ($request->hasFile('attachment')) {
                if ($leave->attachment_path) {
                    Storage::delete($leave->attachment_path);
                }
                $leave->attachment_path = $request->file('attachment')->store('attachments', 'public');
                $leave->save();
            }

            return response()->json(['message' => 'Leave updated successfully!']);
        } catch (Exception $e) {
            return response()->json(['error' => 'Error updating leave.', 'message' => $e->getMessage()], 500);
        }
    }

    // Supprimer un congé (seulement si "on_hold")
    public function deleteLeave($leaveId)
    {
        try {
            $leave = Leave::findOrFail($leaveId);

            if ($leave->status !== 'on_hold') {
                return response()->json(['error' => 'Only pending leaves can be deleted.'], 403);
            }

            if ($leave->attachment_path) {
                Storage::delete($leave->attachment_path);
            }

            $leave->delete();

            return response()->json(['message' => 'Leave deleted successfully!']);
        } catch (Exception $e) {
            return response()->json(['error' => 'Error deleting leave.', 'message' => $e->getMessage()], 500);
        }
    }

    public function downloadAttachment($leaveId)
    {
        try {
            $leave = Leave::findOrFail($leaveId);

            if ($leave->status === 'on_hold') {
                return response()->json(['error' => 'Cannot download attachment for pending leaves.'], 403);
            }

            if (!$leave->attachment_path || !Storage::exists($leave->attachment_path)) {
                return response()->json(['error' => 'File not found.'], 404);
            }

            return Storage::download($leave->attachment_path);
        } catch (Exception $e) {
            return response()->json(['error' => 'Error downloading attachment.', 'message' => $e->getMessage()], 500);
        }
    }
}
