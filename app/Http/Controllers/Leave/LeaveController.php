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
                'leave_type' => 'required|in:sick_leave,personal_leave',
                'other_type' => 'required_if:leave_type,personal_leave',
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
            'other_type' => $leaveType === 'personal_leave' ? $request->other_type : null,
            'remaining_days' => round($remainingDays, 2)
        ], 200);
    }



    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date_format:Y-m-d H:i:s',
            'end_date' => 'required|date_format:Y-m-d H:i:s|after_or_equal:start_date',
            'leave_type' => 'required|in:paternity_leave,maternity_leave,sick_leave,personal_leave',
            'leave_days' => 'required|regex:/^\d+(\.\d{1})?$/',
            'other_type' => 'required_if:leave_type,personal_leave|string|max:255',
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

        if ($request->leave_type == 'sick_leave') {
            $effectiveLeaveDays = max(0, $leaveDaysRequested - 2);
        }

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
                'other_type' => $request->leave_type == 'personal_leave' ? $request->other_type : null,
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
                'other_type' => $request->leave_type == 'personal_leave' ? $request->other_type : null,
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
                'other_type' => $request->leave_type == 'personal_leave' ? $request->other_type : null,
                'leave_days_requested' => $leaveDaysRequested,
                'effective_leave_days' => $effectiveLeaveDays,
                'attachment_path' => $attachmentPath,
                'status' => 'on_hold'
            ]);
        }

        $this->sendLeaveNotification(auth()->user(), $request->leave_type, $request->leave_type === 'personal_leave' ? $request->other_type : null, $leaveEntries);

        return response()->json([
            'message' => 'Leave request stored successfully!',
            'leave' => $leaveEntries,
        ], 201);
    }

    private function sendLeaveNotification($authUser, $leaveType, $otherType = null, $leaveEntries)
    {
        $title = 'New leave request';

        if ($leaveType === 'personal_leave' && $otherType) {
            $message = "{$authUser->first_name} {$authUser->last_name} requested a type of leave {$otherType}.";
        } else {
            $message = "{$authUser->first_name} {$authUser->last_name} requested a type of leave {$leaveType}.";
        }

        if ($authUser->role === 'employee') {
            $receivers = User::whereIn('role', ['admin', 'hr'])->get();
        } elseif ($authUser->role === 'hr') {
            $receivers = User::where('role', 'admin')
                ->orWhere(function ($query) use ($authUser) {
                    $query->where('role', 'hr')
                        ->where('id', '!=', $authUser->id);
                })
                ->get();
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
            $period = Carbon::parse($holiday->start_date)->daysUntil(Carbon::parse($holiday->end_date)->addDay());
            foreach ($period as $date) {
                $holidayDates->push($date->toDateString());
            }
        }

        if ($start->toDateString() === $end->toDateString()) {
            if ($start->isWeekend() || $holidayDates->contains($start->toDateString())) {
                return 0;
            }

            return self::getSameDayFraction($start);
        }

        $total = 0;

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

    public function notifyHROnRejectedLeave(Leave $leave, Request $request)
    {
        $authUser = Auth::user();
        $hrs = User::where('role', 'hr')
            ->when($authUser->role === 'hr', function ($query) use ($authUser) {
                return $query->where('id', '!=', $authUser->id);
            })
            ->pluck('email');

        $rejectionMessage = $request->input('message');
        $subject = 'Demande de congé refusée';

        $startDateFormatted = \Carbon\Carbon::parse($leave->start_date)->format('Y/m/d');
        $endDateFormatted = \Carbon\Carbon::parse($leave->end_date)->format('Y/m/d');

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
                    max-width: 600px;
                    margin: 40px auto;
                    background: #ffffff;
                    padding: 30px;
                    border-radius: 8px;
                    border: 1px solid #e0e0e0;
                    box-shadow: 0px 8px 20px rgba(0, 0, 0, 0.1);
                    text-align: left;
                    font-size: 16px;
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
                    margin-top: 35px;
                    font-size: 14px;
                    color: #888;
                    border-top: 1px solid #e0e0e0;
                    padding-top: 15px;
                    text-align: center;
                    font-style: italic;
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
                    background-color: #007BFF;
                    color: white;
                    padding: 15px;
                    border-radius: 8px 8px 0 0;
                    text-align: center;
                    font-size: 20px;
                    font-weight: bold;
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
            </style>
        </head>
        <body>
            <div class='email-container'>
                <div class='email-header'>
                    <span>PROCAN</span> RH NOTIFICATION
                </div>
                <div class='email-body'>
                    <h2><center>Demande de congé refusée</center></h2>

                    <p><strong>Employé :</strong> {$authUser->first_name} {$authUser->last_name}</p>
                    <p><strong>Email :</strong> {$authUser->email}</p>
                    <p><strong>Type de congé :</strong> {$leave->leave_type}</p>
                    <p><strong>Période :</strong> du <strong>{$startDateFormatted}</strong> au <strong>{$endDateFormatted}</strong></p>

                    <div class='info-block'>
                        <strong>Message de l'utilisateur :</strong>
                        <br>
                        <em>{$rejectionMessage}</em>
                    </div>

                </div>

                <p class='footer'>Ce message a été généré automatiquement. Merci de ne pas y répondre directement.<br>PROCAN RH System</p>
            </div>
        </body>
        </html>
        ";

        foreach ($hrs as $hrEmail) {
            Mail::send([], [], function ($message) use ($hrEmail, $authUser, $subject, $htmlContent) {
                $message->to($hrEmail)
                    ->from($authUser->email, "{$authUser->first_name} {$authUser->last_name}")
                    ->subject($subject)
                    ->html($htmlContent);
            });
        }

        return response()->json(['message' => 'Notification envoyée aux RH.'], 200);
    }



}
