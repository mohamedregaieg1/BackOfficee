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
use Illuminate\Support\Facades\Mail;

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
            $status = (array) $request->input('status', ['approved', 'rejected', 'on_hold']);
            $minYear = Carbon::parse($user->start_date)->year;
            $maxYear = Carbon::now()->year + 1;
            $availableYears = range($minYear, $maxYear);

            $totalLeaveDaysQuery = Leave::where('user_id', $userId)
                ->whereIn('status', $status);

            if ($year) {
                $totalLeaveDaysQuery->whereYear('start_date', $year);
            }

            if ($typeLeave) {
                $totalLeaveDaysQuery->where('leave_type', $typeLeave);
            }

            $totalLeaveDays = $totalLeaveDaysQuery->sum('effective_leave_days');

            $leavesQuery = Leave::where('user_id', $userId)
                ->whereIn('status', $status)
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
                }

                return $leaveData;
            });
            $totalRequestedLeaveDays = $leaves->sum('leave_days_requested');
            $totalEffectiveLeaveDays = $leaves->sum('effective_leave_days');

            $response = [
                'available_years' => $availableYears,
                'total_leave_days' => $totalLeaveDays,
                'total_requested_leave_days' => $totalRequestedLeaveDays,
                'total_effective_leave_days' => $totalEffectiveLeaveDays,
                'data' => $data,
                'meta' => [
                    'selected_year' => $year,
                    'selected_type_leave' => $typeLeave,
                    'selected_statuses' => $status,
                    'current_page' => $leaves->currentPage(),
                    'per_page' => $leaves->perPage(),
                    'total_pages' => $leaves->lastPage(),
                    'total_leaves' => $leaves->total(),
                ],
                'full_name' => "{$user->first_name} {$user->last_name}",
            ];

            return response()->json($response);

        } catch (\Illuminate\Validation\ValidationException $ve) {
            return response()->json($ve->errors(), 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An unexpected error occurred.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }


    public function showLeavesForEmployee(Request $request)
    {
        try {
            $authUser = Auth::user();
            $userId = $authUser->id;
            $year = $request->input('year');

            $statusParam = $request->input('status');
            $statusFilter = [];

            if (is_array($statusParam)) {
                $statusFilter = $statusParam;
            } elseif (is_string($statusParam)) {
                $statusFilter = [$statusParam];
            } elseif (is_null($statusParam)) {
                $statusFilter = ['approved', 'rejected', 'on_hold'];
            }

            $leaveTypeFilter = $request->input('leave_type');

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

            if (!empty($statusFilter)) {
                $leavesQuery->whereIn('status', $statusFilter);
            }

            if (!empty($leaveTypeFilter)) {
                if (is_array($leaveTypeFilter)) {
                    $leavesQuery->whereIn('leave_type', $leaveTypeFilter);
                } else {
                    $leavesQuery->where('leave_type', $leaveTypeFilter);
                }
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

                if ($leave->leave_type === 'personal_leave') {
                    $leaveData['other_type'] = $leave->other_type;
                }

                return $leaveData;
            });

            $stats = [];
            if ($year) {
                $stats['total_effective_days'] = Leave::where('user_id', $userId)
                    ->whereYear('start_date', $year)
                    ->sum('effective_leave_days');

                $stats['status_counts'] = Leave::where('user_id', $userId)
                    ->whereYear('start_date', $year)
                    ->selectRaw('status, COUNT(*) as count')
                    ->groupBy('status')
                    ->pluck('count', 'status')
                    ->all();
            }

            return response()->json([
                'available_years' => $availableYears,
                'data' => $data,
                'meta' => [
                    'selected_year' => $year,
                    'current_page' => $leaves->currentPage(),
                    'per_page' => $leaves->perPage(),
                    'total_pages' => $leaves->lastPage(),
                    'total_leaves' => $leaves->total(),
                ],
                'stats' => $stats,
            ]);
        } catch (\Illuminate\Validation\ValidationException $ve) {
            return response()->json($ve->errors(), 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An unexpected error occurred.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }



    public function updateLeaveForAdmin(Request $request, $leaveId)
    {
        try {
            $validated = $request->validate([
                'leave_type' => 'required|in:sick_leave',
                'leave_days_requested' => 'required|numeric|min:1',
                'effective_leave_days' => 'nullable|numeric|min:0',
            ]);

            $leave = Leave::findOrFail($leaveId);

            if ($leave->leave_type !== 'sick_leave') {
                return response()->json(['error' => 'This leave cannot be updated, it is not a sick leave.'], 400);
            }

            $leave->leave_days_requested = $validated['leave_days_requested'];
            $leave->effective_leave_days = $validated['effective_leave_days'] ?? $leave->effective_leave_days;

            $leave->save();

            return response()->json(['message' => 'Leave updated successfully!']);
        } catch (\Illuminate\Validation\ValidationException $ve) {
            return response()->json($ve->errors(), 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An unexpected error occurred.',
                'details' => $e->getMessage(),
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

            if (in_array($leave->status, ['approved', 'rejected'])) {
                return response()->json(['error' => 'This leave status cannot be modified as it is already approved or rejected.'], 400);
            }

            $leave->status = $validated['status'];
            $leave->save();

            $this->sendLeaveStatusNotification($leave);
            $this->sendEmailToSender($leave);


            return response()->json(['message' => 'Leave status updated successfully!']);
        } catch (\Illuminate\Validation\ValidationException $ve) {
            return response()->json($ve->errors(), 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An unexpected error occurred.',
                'details' => $e->getMessage(),
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
            'leave_id' => $leave->id,
            'title' => $title,
            'message' => $message,
        ]);

        broadcast(new NewNotificationEvent($notification))->toOthers();
    }

    protected function sendEmailToSender($leave)
{
    $notification = Notification::where('leave_id', $leave->id)->first();

    if (!$notification) {
        return;
    }

    $sender = $notification->sender;

    if (!$sender || !$sender->email) {
        return;
    }

    $startDateFormatted = \Carbon\Carbon::parse($leave->start_date)->format('Y/m/d');
    $endDateFormatted = \Carbon\Carbon::parse($leave->end_date)->format('Y/m/d');

    $leaveTypeLabel = $leave->leave_type === 'other' && $leave->other_type ? $leave->other_type : $leave->leave_type;

    $htmlContent = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; background-color: #f9fafb; margin: 0; padding: 0; line-height: 1.6; }
            .email-border {
                max-width: 650px; margin: 40px auto; border: 2px solid #000000; border-radius: 12px; overflow: hidden;
                box-shadow: 0px 8px 20px rgba(0, 0, 0, 0.1);
            }
            .email-container {
                max-width: 600px; margin: 0 auto; background: #ffffff;
                padding: 30px; border-radius: 12px;
                font-size: 16px; color: #333333;
            }
            .email-header {
                background-color: #1e40af; color: white;
                padding: 20px; border-radius: 12px 12px 0 0;
                text-align: center; font-size: 24px; font-weight: bold;
                letter-spacing: 1px;
            }
            h2 { color: #2c3e50; text-align: center; margin-bottom: 20px; font-size: 20px; }
            p { color: #555; font-size: 16px; margin: 10px 0; }
            .highlight { color: #1e40af; font-weight: bold; }
            .button {
                display: inline-block; background: transparent; color: #007BFF;
                padding: 12px 28px; border: 2px solid #007BFF; border-radius: 8px;
                text-decoration: none; font-weight: bold; font-size: 16px; transition: all 0.3s ease;
                box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
            }
            .button:hover {
                background: #007BFF; color: #fff; transform: translateY(-2px);
                box-shadow: 0px 6px 10px rgba(0, 0, 0, 0.15);
            }
            .footer {
                margin-top: 30px; font-size: 13px; color: #888;
                text-align: center; border-top: 1px solid #ddd; padding-top: 15px;
            }
            .info-section {
                background-color: #f8f9fa; padding: 15px; border-radius: 8px;
                margin-top: 20px; font-size: 15px; color: #444;
            }
            .icon { width: 20px; height: 20px; vertical-align: middle; margin-right: 10px; }
        </style>
    </head>
    <body>
        <div class='email-border'>
            <div class='email-container'>
                <div class='email-header'>PROCAN | Leave Status Update</div>
                <h2>Your Leave Request Has Been {$leave->status}</h2>
                <p>Dear {$sender->first_name} {$sender->last_name},</p>
                <p>Your leave request has been reviewed and the status has been updated to <strong>{$leave->status}</strong>.</p>

                <div class='info-section'>
                    <p><img src='https://img.icons8.com/ios-glyphs/30/user.png' class='icon' /> <strong>Employee Name:</strong> {$sender->first_name} {$sender->last_name}</p>
                    <p><img src='https://img.icons8.com/ios-glyphs/30/calendar.png' class='icon' /> <strong>Type of Leave:</strong> {$leaveTypeLabel}</p>
                    <p><img src='https://img.icons8.com/ios-glyphs/30/time-span.png' class='icon' /> <strong>Period:</strong> from <span class='highlight'>{$startDateFormatted}</span> to <span class='highlight'>{$endDateFormatted}</span></p>
                    <p><img src='https://img.icons8.com/ios-glyphs/30/counter.png' class='icon' /> <strong>Number of Days:</strong> {$leave->effective_leave_days}</p>
                </div>

                <p>If you have any questions or need further clarification, please contact the HR department.</p>

                <p class='footer'>
                    This is an automated message. Please do not reply directly to this email.<br>
                    For any inquiries, contact the HR department at <a href='mailto:info@procan-group.com'>info@procan-group.com</a>.<br>
                    PROCAN HR System © " . date('Y') . "
                </p>
            </div>
        </div>
    </body>
    </html>
    ";

    Mail::send([], [], function ($message) use ($sender, $htmlContent) {
        $message->to($sender->email)
            ->from('noreply@procan.com', 'PROCAN HR System')
            ->subject('Leave Status Update')
            ->html($htmlContent);
    });
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
                'leave_type' => 'required|in:paternity_leave,maternity_leave,sick_leave,personal_leave,other',
                'other_type' => 'required_if:leave_type,other|string|max:255',
                'attachment' => 'required_if:leave_type,sick_leave,maternity_leave,paternity_leave|file|mimes:pdf,jpg,jpeg,png|max:2048',
            ], [
                'other_type.required_if' => 'The "Other leave type" field is required when leave type is "personal_leave".',
                'attachment.required_if' => 'An attachment is required for this type of leave.',
            ]);

            if (in_array($validated['leave_type'], ['maternity_leave', 'paternity_leave'])) {
                $startDate = Carbon::parse($leave->start_date);
                $oneYearAgo = $startDate->copy()->subYear();
                $currentYearStart = $startDate->copy()->startOfYear();
                $currentYearEnd = $startDate->copy()->endOfYear();

                $existingLeaveInOneYear = Leave::where('user_id', $authUser->id)
                    ->where('leave_type', $validated['leave_type'])
                    ->whereBetween('start_date', [$oneYearAgo, $startDate])
                    ->whereIn('status', ['on_hold', 'approved'])
                    ->where('id', '!=', $leaveId)
                    ->exists();

                $existingLeaveInCurrentYear = Leave::where('user_id', $authUser->id)
                    ->where('leave_type', $validated['leave_type'])
                    ->whereBetween('start_date', [$currentYearStart, $currentYearEnd])
                    ->whereIn('status', ['on_hold', 'approved'])
                    ->where('id', '!=', $leaveId)
                    ->exists();

                if ($existingLeaveInOneYear || $existingLeaveInCurrentYear) {
                    return response()->json(['error' => 'Already taken this type of leave within the last year or in the current year.'], 403);
                }
            }

            $leave->leave_type = $validated['leave_type'];

            if ($validated['leave_type'] === 'other') {
                $leave->other_type = $validated['other_type'];
            } else {
                $leave->other_type = null;
            }

            if (in_array($validated['leave_type'], ['sick_leave', 'maternity_leave', 'paternity_leave']) && $request->hasFile('attachment')) {
                if ($leave->attachment_path) {
                    Storage::disk('public')->delete(str_replace(env('STORAGE') . '/attachments/', '', $leave->attachment_path));
                }

                $file = $request->file('attachment');
                $path = $file->store('attachments', 'public');
                $leave->attachment_path = env('STORAGE') . '/attachments/' . basename($path);
            }

            if ($validated['leave_type'] === 'sick_leave') {
                $requestedDays = $leave->leave_days_requested;

                if (!$requestedDays) {
                    $requestedDays = $this->getWorkingDays($leave->start_date, $leave->end_date);
                    $leave->leave_days_requested = $requestedDays;
                }

                $leave->effective_leave_days = max(0, $requestedDays - 2);
            } else {
                $leave->effective_leave_days = $leave->leave_days_requested;
            }

            $leave->save();

            return response()->json(['message' => 'Leave updated successfully!']);
        } catch (\Illuminate\Validation\ValidationException $ve) {
            return response()->json($ve->errors(), 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An unexpected error occurred.',
                'details' => $e->getMessage(),
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
        } catch (\Illuminate\Validation\ValidationException $ve) {
            return response()->json($ve->errors(), 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An unexpected error occurred.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }
}
