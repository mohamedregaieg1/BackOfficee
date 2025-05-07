<?php

namespace App\Http\Controllers\Leave;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Leave;
use App\Models\FixedLeaves;
use App\Models\LeavesBalance;
use App\Models\User;
use App\Models\Notification;
use App\Events\NewNotificationEvent;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Jobs\SendLeaveRequestNotificationJob;
use App\Jobs\NotifyHRRejectedLeaveJob;



class LeaveController extends Controller
{
    public function calculateLeaveDays(Request $request)
    {
        $leaveType = $request->leave_type;

        if (in_array($leaveType, ['maternity_leave', 'paternity_leave'])) {
            $validator = Validator::make($request->all(), [
                'start_date' => 'required|date_format:Y-m-d H:i:s',
                'leave_type' => 'required|in:paternity_leave,maternity_leave',
            ]);
        } else {
            $validator = Validator::make($request->all(), [
                'start_date' => 'required|date_format:Y-m-d H:i:s',
                'end_date' => 'required|date_format:Y-m-d H:i:s|after_or_equal:start_date',
                'leave_type' => 'required|in:sick_leave,personal_leave,other',
                'other_type' => 'required_if:leave_type,other',
            ]);
        }

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = Auth::user();
        $start = Carbon::parse($request->start_date);

        if (in_array($leaveType, ['maternity_leave', 'paternity_leave'])) {
            $oneYearAgo = $start->copy()->subYear();

            $alreadyTaken = Leave::where('user_id', $user->id)
                ->where('leave_type', $leaveType)
                ->whereBetween('start_date', [$oneYearAgo, $start])
                ->whereIn('status', ['on_hold', 'approved'])
                ->exists();

            if ($alreadyTaken) {
                return response()->json([
                    'message' => "already taken"
                ], 422);
            }

            $fixedLeave = FixedLeaves::where('leave_type', $leaveType)->first();

            if (!$fixedLeave) {
                return response()->json(['message' => 'Fixed leave not found.'], 404);
            }

            $leaveDays = $fixedLeave->max_days;
            $end = $start->copy()->addDays($leaveDays - 1)->setTime(8, 0, 0);

            $remainingDays = $this->getRemainingLeaveDays($user->id, $leaveType, $leaveDays);

            return response()->json([
                'start_date' => $start->toDateTimeString(),
                'end_date' => $end->toDateTimeString(),
                'leave_days' => $leaveDays,
                'leave_type' => $leaveType,
                'other_type' => null,
                'remaining_days' => round($remainingDays, 2)
            ], 200);
        }

        $leaveDays = $this->getWorkingDays($request->start_date, $request->end_date);
        $remainingDays = $this->getRemainingLeaveDays($user->id, $leaveType, $leaveDays);

        return response()->json([
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'leave_days' => $leaveDays,
            'leave_type' => $leaveType,
            'other_type' => $leaveType === 'other' ? $request->other_type : null,
            'remaining_days' => round($remainingDays, 2)
        ], 200);
    }



    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date_format:Y-m-d H:i:s',
            'end_date' => 'required|date_format:Y-m-d H:i:s|after_or_equal:start_date',
            'leave_type' => 'required|in:paternity_leave,maternity_leave,sick_leave,personal_leave,other',
            'leave_days' => 'required|regex:/^\d+(\.\d{1})?$/',
            'other_type' => 'required_if:leave_type,other|string|max:255',
            'attachment' => 'required_if:leave_type,sick_leave,maternity_leave,paternity_leave|file',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $attachmentPath = null;
        if (
            in_array($request->leave_type, ['sick_leave', 'maternity_leave', 'paternity_leave'])
            && $request->hasFile('attachment')
        ) {
            $file = $request->file('attachment');
            $path = $file->store('attachments', 'public');
            $filename = basename($path);
            $attachmentPath = env('STORAGE') . '/attachments/' . $filename;
        }

        if (in_array($request->leave_type, ['maternity_leave', 'paternity_leave'])) {
            $start = Carbon::parse($request->start_date);
            $end = Carbon::parse($request->end_date);
            $leaveDays = $start->diffInDays($end) + 1;
        } else {
            $leaveDays = $this->getWorkingDays($request->start_date, $request->end_date);
        }

        $leaveDaysRequested = $this->calculateLeaveDaysRequested($request->leave_type, $leaveDays);
        $effectiveLeaveDays = $leaveDaysRequested;


        $start = Carbon::parse($request->start_date);
        $end = Carbon::parse($request->end_date);
        $leaveEntries = [];

        if ($start->year != $end->year) {
            $midYearDate = Carbon::create($start->year, 12, 31);
            $leaveEntries[] = Leave::create([
                'user_id' => auth()->id(),
                'start_date' => $request->start_date,
                'end_date' => $midYearDate->toDateString() . ' 08:00:00',
                'leave_type' => $request->leave_type,
                'other_type' => $request->leave_type == 'other' ? $request->other_type : null,
                'leave_days_requested' => $this->getWorkingDays($request->start_date, $midYearDate->toDateString() . ' 08:00:00'),
                'effective_leave_days' => $this->calculateLeaveDaysRequested($request->leave_type, $this->getWorkingDays($request->start_date, $midYearDate->toDateString() . ' 23:59:59')),
                'attachment_path' => $attachmentPath,
                'status' => 'on_hold'
            ]);

            $leaveEntries[] = Leave::create([
                'user_id' => auth()->id(),
                'start_date' => $midYearDate->addDay()->toDateString() . ' 08:00:00',
                'end_date' => $request->end_date,
                'leave_type' => $request->leave_type,
                'other_type' => $request->leave_type == 'other' ? $request->other_type : null,
                'leave_days_requested' => $this->getWorkingDays($midYearDate->toDateString() . ' 08:00:00', $request->end_date),
                'effective_leave_days' => $this->calculateLeaveDaysRequested($request->leave_type, $this->getWorkingDays($midYearDate->toDateString() . ' 00:00:00', $request->end_date)),
                'attachment_path' => $attachmentPath,
                'status' => 'on_hold'
            ]);
        } else {
            $leaveEntries[] = Leave::create([
                'user_id' => auth()->id(),
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'leave_type' => $request->leave_type,
                'other_type' => $request->leave_type == 'other' ? $request->other_type : null,
                'leave_days_requested' => $leaveDays,
                'effective_leave_days' => $effectiveLeaveDays,
                'attachment_path' => $attachmentPath,
                'status' => 'on_hold'
            ]);
        }

        $this->sendLeaveNotification(auth()->user(), $request->leave_type, $request->leave_type === 'other' ? $request->other_type : null, $leaveEntries);
        $this->notifyAdminOnLeaveRequest(auth()->user(), $request->leave_type, $request->leave_type === 'other' ? $request->other_type : null, $leaveEntries);
        return response()->json([
            'message' => 'Leave request stored successfully!',
            'leave' => $leaveEntries,
        ], 201);
    }

    private function sendLeaveNotification($authUser, $leaveType, $otherType = null, $leaveEntries)
    {
        $title = 'New leave request';
        if ($leaveType === 'other' && $otherType) {
            $message = "{$authUser->first_name} {$authUser->last_name} requested a type of leave: {$otherType}.";
        } else {
            $message = "{$authUser->first_name} {$authUser->last_name} requested a type of leave: {$leaveType}.";
        }

        if ($authUser->role === 'employee') {
            $receivers = User::whereIn('role', ['admin', 'hr'])->get();
        } elseif ($authUser->role === 'hr') {
            $receivers = User::where(function ($query) use ($authUser) {
                $query->where('role', 'admin')
                    ->orWhere(function ($query) use ($authUser) {
                        $query->where('role', 'hr')
                            ->where('id', '!=', $authUser->id);
                    });
            })->get();
        } else {
            $receivers = collect();
        }

        foreach ($leaveEntries as $leaveEntry) {
            foreach ($receivers as $receiver) {
                $notification = Notification::create([
                    'sender_id' => $authUser->id,
                    'receiver_id' => $receiver->id,
                    'title' => $title,
                    'message' => $message,
                    'leave_id' => $leaveEntry->id,
                ]);
                broadcast(new NewNotificationEvent($notification))->toOthers();
            }
        }
    }


    private function notifyAdminOnLeaveRequest($authUser, $leaveType, $otherType, $leaveEntries)
    {
        $admins = User::whereIn('role', ['admin', 'hr'])
            ->when($authUser->role === 'hr', function ($query) use ($authUser) {
                return $query->where('id', '!=', $authUser->id);
            })
            ->pluck('email');

        foreach ($leaveEntries as $leaveEntry) {
            $startDateFormatted = \Carbon\Carbon::parse($leaveEntry->start_date)->format('Y/m/d');
            $endDateFormatted = \Carbon\Carbon::parse($leaveEntry->end_date)->format('Y/m/d');

            $leaveLabel = $leaveType === 'other' && $otherType ? $otherType : $leaveType;

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
                    <div class='email-header'>PROCAN | Leave Request Notification</div>
                    <h2>New Leave Request Submitted</h2>
                    <p>Dear Admin/HR Team,</p>
                    <p>A new leave request has been submitted by an employee. Please review the details below:</p>

                    <div class='info-section'>
                        <p><img src='https://img.icons8.com/ios-glyphs/30/user.png' class='icon' /> <strong>Employee Name:</strong> {$authUser->first_name} {$authUser->last_name}</p>
                        <p><img src='https://img.icons8.com/ios-glyphs/30/email.png' class='icon' /> <strong>Email:</strong> {$authUser->email}</p>
                        <p><img src='https://img.icons8.com/ios-glyphs/30/calendar.png' class='icon' /> <strong>Type of Leave:</strong> {$leaveLabel}</p>
                        <p><img src='https://img.icons8.com/ios-glyphs/30/time-span.png' class='icon' /> <strong>Period:</strong> from <span class='highlight'>{$startDateFormatted}</span> to <span class='highlight'>{$endDateFormatted}</span></p>
                    </div>

                    <p>To review and approve this request, please click the button below:</p>
                    <a href='http://localhost:4200/#/login' class='button'>Review Leave Request</a>

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

            foreach ($admins as $adminEmail) {
                dispatch(new SendLeaveRequestNotificationJob($adminEmail, $htmlContent));
            }
        }
    }

    private function calculateLeaveDaysRequested($leaveType, $leaveDays)
    {
        if ($leaveType == 'sick_leave') {
            return max(0, $leaveDays - 2);
        }

        return $leaveDays;
    }

    public function show($id)
    {
        $leave = Leave::findOrFail($id);

        return response()->json([
            'leave' => $leave
        ]);
    }


    private function getRemainingLeaveDays($userId, $leaveType, $requestedLeaveDays)
    {
        $specificLeaves = ['paternity_leave', 'maternity_leave', 'sick_leave'];

        if (in_array($leaveType, $specificLeaves)) {
            $fixedLeave = FixedLeaves::where('leave_type', $leaveType)->first();

            if (!$fixedLeave) {
                return 0;
            }

            if ($leaveType === 'sick_leave') {
                $sickLeaves = Leave::where('user_id', $userId)
                    ->where('leave_type', 'sick_leave')
                    ->whereIn('status', ['on_hold', 'approved'])
                    ->get();

                $diffSum = $sickLeaves->sum(function ($leave) {
                    return $leave->leave_days_requested - $leave->effective_leave_days;
                });

                $remainingDays = $fixedLeave->max_days - $diffSum - $requestedLeaveDays;
            } else {
                $usedDays = Leave::where('user_id', $userId)
                    ->where('leave_type', $leaveType)
                    ->whereIn('status', ['on_hold', 'approved'])
                    ->sum('leave_days_requested');

                $remainingDays = $fixedLeave->max_days - $usedDays - $requestedLeaveDays;
            }

            return $remainingDays;
        } else {
            $leaveBalance = LeavesBalance::where('user_id', $userId)->first();

            if (!$leaveBalance) {
                return 0;
            }

            $usedDays = Leave::where('user_id', $userId)
                ->whereIn('leave_type', ['vacation', 'travel_leave', 'other'])
                ->whereIn('status', ['on_hold', 'approved'])
                ->sum('effective_leave_days');

            $remainingDays = $leaveBalance->leave_day_limit - $usedDays - $requestedLeaveDays;

            return $remainingDays;
        }
    }

    public static function getWorkingDays($startDate, $endDate)
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        $holidays = \App\Models\PublicHoliday::where(function ($query) use ($start, $end) {
            $query->whereBetween('start_date', [$start->toDateString(), $end->toDateString()])
                ->orWhereBetween('end_date', [$start->toDateString(), $end->toDateString()])
                ->orWhere(function ($query) use ($start, $end) {
                    $query->where('start_date', '<=', $start->toDateString())
                        ->where('end_date', '>=', $end->toDateString());
                });
        })->get();
        $holidayDates = collect();
        foreach ($holidays as $holiday) {
            $startDate = Carbon::parse($holiday->start_date);
            $endDate = Carbon::parse($holiday->end_date);
            $period = $startDate->daysUntil($endDate);
            foreach ($period as $date) {
                $holidayDates->push($date->toDateString());
            }
        }

        $total = 0;

        if ($start->toDateString() === $end->toDateString()) {
            if ($start->isWeekend() || $holidayDates->contains($start->toDateString())) {
                return 0;
            }
            return self::getSameDayFraction($start);
        }

        if (!$start->isWeekend() && !$holidayDates->contains($start->toDateString())) {
            $total += self::getStartDayFraction($start);
        }

        $current = $start->copy()->addDay();
        while ($current->lt($end->copy()->startOfDay())) {
            if (!$current->isWeekend() && !$holidayDates->contains($current->toDateString())) {
                $total += 1;
            }
            $current->addDay();
        }

        if (!$end->isWeekend() && !$holidayDates->contains($end->toDateString())) {
            $total += self::getEndDayFraction($end);
        }

        Log::info('Total des jours ouvrables calculés : ', ['total' => $total]);

        return $total;
    }

    private static function getSameDayFraction(Carbon $start)
    {
        $startHour = $start->hour;

        if ($startHour === 8) {
            return 1;
        }

        if ($startHour === 12) {
            return 0.5;
        }

        if ($startHour === 17) {
            return 0.5;
        }

        return 1;
    }

    private static function getStartDayFraction(Carbon $start)
    {
        $startHour = $start->hour;

        if ($startHour === 8) {
            return 1;
        }

        if ($startHour === 12) {
            return 0.5;
        }

        if ($startHour === 17) {
            return 0.5;
        }

        return 1;
    }

    private static function getEndDayFraction(Carbon $end)
    {
        $endHour = $end->hour;

        if ($endHour === 17) {
            return 0.5;
        }

        if ($endHour === 12) {
            return 0.5;
        }

        if ($endHour === 8) {
            return 1;
        }

        return 1;
    }

    public function downloadLeavePdf($leaveId)
    {
        $leave = Leave::where('id', $leaveId)
            ->where('status', 'approved')
            ->where('user_id', Auth::id())
            ->first();

        if (!$leave) {
            return response()->json(['message' => 'Unauthorized or leave not found'], 403);
        }

        $user = Auth::user();
        $companyLogoPath = ($user->company == 'procan')
            ? public_path('dist/img/logo-procan.webp')
            : public_path('dist/img/logo-Adequate.webp');

        $statusImagePath = public_path('dist/img/approved.webp');
        $companyLogoBase64 = base64_encode(file_get_contents($companyLogoPath));
        $statusImageBase64 = base64_encode(file_get_contents($statusImagePath));

        $view = view('pdf.leave', compact('leave', 'companyLogoBase64', 'statusImageBase64'))->render();

        $options = new Options();
        $options->set('defaultFont', 'Arial');

        $pdf = new Dompdf($options);
        $pdf->loadHtml($view);
        $pdf->setPaper('A4', 'portrait');
        $pdf->render();

        return response($pdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="leave_request_' . $leave->id . '.pdf"');
    }


    public function notifyHROnRejectedLeave(Request $request)
    {
        try {
            $validated = $request->validate([
                'start_date' => 'required|date_format:Y-m-d H:i:s',
                'leave_type' => 'required|in:paternity_leave,maternity_leave',
                'message' => 'nullable|string',
            ]);

            $authUser = Auth::user();

            $hrs = User::where('role', 'hr')
                ->when($authUser->role === 'hr', function ($query) use ($authUser) {
                    return $query->where('id', '!=', $authUser->id);
                })
                ->pluck('email');

            $rejectionMessage = $request->input('message', 'Aucun message fourni');

            $fixedLeave = FixedLeaves::where('leave_type', $validated['leave_type'])->first();

            if (!$fixedLeave) {
                return response()->json(['error' => 'Fixed leave type not found.'], 404);
            }

            $startDate = Carbon::parse($validated['start_date']);
            $endDate = $startDate->copy()->addDays($fixedLeave->max_days - 1)->setTime(8, 0, 0);

            $startDateFormatted = $startDate->format('Y/m/d');
            $endDateFormatted = $endDate->format('Y/m/d');

            $htmlContent = "
             <html>
            <head>
                <style>
                    body {
                        font-family: 'Arial', sans-serif;
                        background-color: #f4f6f9;
                        margin: 0;
                        padding: 0;
                    }
                    .email-container {
                        max-width: 650px; margin: 40px auto;
                        margin: 40px auto;
                        background: #ffffff;
                        padding: 30px;
                        border-radius: 12px;
                        border: 2px solid #000000;
                        overflow: hidden;
                        box-shadow: 0px 8px 20px rgba(0, 0, 0, 0.1);
                        text-align: left;
                        font-size: 16px;
                    }
                    .email-border {
                     border: 2px solid #000000;
                     border-radius: 12px;
                     overflow: hidden;
                     box-shadow: 0px 8px 20px rgba(0, 0, 0, 0.1);
                    }
                    .logo-container {
                        text-align: center;
                        margin-bottom: 30px;
                    }
                    .logo {
                        font-size: 32px;
                        font-weight: bold;
                        color: #007BFF;
                    }
                    h2 {
                        color: #e74c3c;
                        font-size: 24px;
                        font-weight: bold;
                        margin-bottom: 25px;
                    }
                    p {
                        color: #555;
                        font-size: 16px;
                        line-height: 1.6;
                        margin: 10px 0;
                    }
                    .footer {
                        margin-top: 30px; font-size: 13px; color: #888;
                        text-align: center; border-top: 1px solid #ddd; padding-top: 15px;
                    }
                    .info-block {
                        background: #f9f9f9;
                        padding: 20px;
                        border-radius: 8px;
                        margin-top: 20px;
                        border-left: 5px solid #007BFF;
                        font-size: 15px;
                    }
                    .info-block strong {
                        font-size: 16px;
                        color: #333;
                    }
                .email-header {
                    background-color: #1e40af; color: white;
                    padding: 20px; border-radius: 12px 12px 0 0;
                    text-align: center; font-size: 24px; font-weight: bold;
                    letter-spacing: 1px;
                }
                    .email-header span {
                        color:rgb(255, 255, 255);
                    }
                    .email-body {
                        margin-top: 20px;
                        padding: 15px;
                        background-color: #ffffff;
                        border-radius: 8px;
                        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
                    }
                    .email-body p {
                        margin: 5px 0;
                    }
                    .icon {
                        width: 20px;
                        height: 20px;
                        vertical-align: middle;
                        margin-right: 10px;
                    }
                </style>
            </head>
            <body>
                <div class='email-container'>
                    <div class='email-header'>
                        <span>PROCAN</span> HR NOTIFICATION
                    </div>
                    <div class='email-body'>
                        <h2><center>Leave Request Rejected</center></h2>

                        <p><img src='https://img.icons8.com/ios-glyphs/30/user.png' class='icon' /> <strong>Employee:</strong> {$authUser->first_name} {$authUser->last_name}</p>
                        <p><img src='https://img.icons8.com/ios-glyphs/30/email.png' class='icon' /> <strong>Email:</strong> {$authUser->email}</p>
                        <p><img src='https://img.icons8.com/ios-glyphs/30/calendar.png' class='icon' /> <strong>Type of Leave:</strong> {$validated['leave_type']}</p>
                        <p><img src='https://img.icons8.com/ios-glyphs/30/time-span.png' class='icon' /> <strong>Period:</strong> from <strong>{$startDateFormatted}</strong> to <strong>{$endDateFormatted}</strong></p>

                        <div class='info-block'>
                            <strong>User's Message:</strong>
                            <br>
                            <em>{$rejectionMessage}</em>
                        </div>

                    </div>

                    <p class='footer'>
                        This is an automated message. Please do not reply directly to this email.<br>
                        For any inquiries, contact the HR department at <a href='mailto:info@procan-group.com'>info@procan-group.com</a>.<br>
                        PROCAN HR System © " . date('Y') . "
                    </p>
                </div>
            </body>
            </html>
        ";

            foreach ($hrs as $hrEmail) {
                dispatch(new NotifyHRRejectedLeaveJob($hrEmail, $authUser, $htmlContent));
            }


            return response()->json(['message' => 'Notification envoyée aux RH.'], 200);

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
