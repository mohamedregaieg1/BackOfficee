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
use App\Models\Notification;
use Exception;
use App\Events\NewNotificationEvent;

class ViewLeaveController extends Controller
{
    public function showLeavesForAdmin(Request $request, $userId)
    {
        try {
            $user = User::findOrFail($userId);
            $authUser = Auth::user();

            if (!in_array($authUser->role, ['admin', 'hr'])) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $year = $request->input('year');
            $typeLeave = $request->input('type_leave');

            $minYear = Carbon::parse($user->start_date)->year;
            $maxYear = Carbon::now()->year + 1;
            $availableYears = range($minYear, $maxYear);

            $totalLeaveDaysQuery = Leave::where('user_id', $userId)
                ->where('status', 'approved');

            if ($year) {
                $totalLeaveDaysQuery->whereYear('start_date', $year);
            }

            if ($typeLeave) {
                $totalLeaveDaysQuery->where('leave_type', $typeLeave);
            }

            $totalLeaveDays = $totalLeaveDaysQuery->sum('effective_leave_days');

            $leavesQuery = Leave::where('user_id', $userId)
                ->select(
                    'id',
                    'start_date',
                    'end_date',
                    'leave_type',
                    'other_type',
                    'leave_days_requested',
                    'effective_leave_days',
                    'attachment_path',
                    'status'
                );

            if ($year) {
                $leavesQuery->whereYear('start_date', $year);
            }

            if ($typeLeave) {
                $leavesQuery->where('leave_type', $typeLeave);
            }

            $leaves = $leavesQuery->orderBy('created_at', 'desc')
                ->paginate(6)
                ->appends($request->query());

            $data = $leaves->map(function ($leave) {
                $leaveData = [
                    'id' => $leave->id,
                    'start_date' => $leave->start_date,
                    'end_date' => $leave->end_date,
                    'leave_type' => $leave->leave_type,
                    'status' => $leave->status,
                    'leave_days_requested' => $leave->leave_days_requested,
                    'effective_leave_days' => $leave->effective_leave_days,
                    'attachment_path' => $leave->attachment_path ? asset($leave->attachment_path) : null,
                ];

                if ($leave->leave_type === 'other') {
                    $leaveData['other_type'] = $leave->other_type;
                    unset($leaveData['leave_type']);
                }

                return $leaveData;
            });

            $response = [
                'available_years' => $availableYears,
                'total_leave_days' => $totalLeaveDays,
                'data' => $data,
                'meta' => [
                    'selected_year' => $year,
                    'selected_type_leave' => $typeLeave,
                    'current_page' => $leaves->currentPage(),
                    'per_page' => $leaves->perPage(),
                    'total_pages' => $leaves->lastPage(),
                    'total_leaves' => $leaves->total(),
                ],
                'full_name' => "{$user->first_name} {$user->last_name}",
            ];

            return response()->json($response);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An error occurred while retrieving leave data.',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function showLeavesForEmployee(Request $request)
    {
        try {
            $authUser = Auth::user();
            $userId = $authUser->id;
            $year = $request->input('year');
            $minYear = Carbon::parse($authUser->start_date)->year;
            $maxYear = Carbon::now()->year + 1;
            $availableYears = range($minYear, $maxYear);

            $leavesQuery = Leave::where('user_id', $userId)
                ->select(
                    'id',
                    'start_date',
                    'end_date',
                    'leave_type',
                    'other_type',
                    'leave_days_requested',
                    'effective_leave_days',
                    'attachment_path',
                    'status'
                );

            if ($year) {
                $leavesQuery->whereYear('start_date', $year);
            }

            $leaves = $leavesQuery->orderBy('created_at', 'desc')
                ->paginate(6)
                ->appends($request->query());

            $data = $leaves->map(function ($leave) {
                $leaveData = [
                    'id' => $leave->id,
                    'start_date' => $leave->start_date,
                    'end_date' => $leave->end_date,
                    'leave_type' => $leave->leave_type,
                    'status' => $leave->status,
                    'leave_days_requested' => $leave->leave_days_requested,
                    'effective_leave_days' => $leave->effective_leave_days,
                    'attachment_path' => $leave->attachment_path ? asset($leave->attachment_path) : null,
                ];

                if ($leave->leave_type === 'other') {
                    $leaveData['other_type'] = $leave->other_type;
                    unset($leaveData['leave_type']);
                }

                return $leaveData;
            });

            $response = [
                'available_years' => $availableYears,
                'data' => $data,
                'meta' => [
                    'selected_year' => $year,
                    'current_page' => $leaves->currentPage(),
                    'per_page' => $leaves->perPage(),
                    'total_pages' => $leaves->lastPage(),
                    'total_leaves' => $leaves->total(),
                ],
            ];

            return response()->json($response);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An error occurred while retrieving leave data.',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function updateLeaveForAdmin(Request $request, $leaveId)
    {
        try {
            $validated = $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'leave_type' => 'required|in:vacation,travel_leave,paternity_leave,maternity_leave,sick_leave,other',
                'other_type' => 'nullable|required_if:leave_type,other|string|max:255',
                'leave_days_requested' => 'required|numeric|min:1',
                'effective_leave_days' => 'nullable|numeric|min:0',
            ]);

            $leave = Leave::findOrFail($leaveId);
            $leave->start_date = $validated['start_date'];
            $leave->end_date = $validated['end_date'];
            $leave->leave_type = $validated['leave_type'];
            $leave->other_type = $validated['other_type'] ?? null;
            $leave->leave_days_requested = $validated['leave_days_requested'];
            $leave->save();

            return response()->json(['message' => 'Leave updated successfully!']);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Error updating leave.',
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

            $this->sendLeaveStatusNotification($leave);

            return response()->json(['message' => 'Leave status updated successfully!']);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'An error occurred while updating leave status.',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    protected function sendLeaveStatusNotification(Leave $leave)
    {
        $receiver = $leave->user;
        $sender = Auth::user();
        $statusText = $leave->status === 'approved' ? 'approved' : 'rejected';
        $title = "Update on your leave request";
        $message = "{$sender->first_name} {$sender->last_name} has {$statusText} your leave request for {$leave->leave_type}.";

        $notification = Notification::create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'title' => $title,
            'message' => $message,
        ]);

        broadcast(new NewNotificationEvent($notification))->toOthers();
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
                'leave_type' => 'required|in:vacation,travel_leave,paternity_leave,maternity_leave,sick_leave,other',
                'other_type' => 'nullable|required_if:leave_type,other|string|max:255',
                'attachment' => 'nullable|file|mimes:pdf,jpg,png|max:2048|required_if:leave_type,sick_leave'
            ], [
                'other_type.required_if' => 'The "Other leave_type" field is required if the leave type is "other".',
                'attachment.required_if' => 'An attachment is required for sick leave.'
            ]);

            if ($request->has('leave_type')) {
                $leave->other_type = $validated['leave_type'] === 'other' ? $validated['other_type'] : null;
            }

            if ($request->has('other_type') && $validated['leave_type'] === 'other') {
                $leave->other_type = $validated['other_type'];
            }

            if ($validated['leave_type'] === 'sick_leave') {
                if ($request->hasFile('attachment')) {
                    if ($leave->attachment_path) {
                        Storage::disk('public')->delete(str_replace(env('STORAGE').'/attachments/', '', $leave->attachment_path));
                    }

                    $file = $request->file('attachment');
                    $path = $file->store('attachments', 'public');
                    $filename = basename($path);
                    $leave->attachment_path = env('STORAGE').'/attachments/'.$filename;
                }
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
            return response()->json([
                'error' => 'Error deleting leave.',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
