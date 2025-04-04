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

class LeaveController extends Controller
{
    public function calculateLeaveDays(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date_format:Y-m-d H:i:s',
            'end_date' => 'required|date_format:Y-m-d H:i:s|after_or_equal:start_date',
            'leave_type' => 'required|in:paternity_leave,maternity_leave,sick_leave,vacation,travel_leave,other',
            'other_type' => 'required_if:leave_type,other',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $leaveDays = $this->getWorkingDays($request->start_date, $request->end_date);
        $user = Auth::user();
        $remainingDays = $this->getRemainingLeaveDays($user->id, $request->leave_type, $leaveDays);
        $remainingDays = round($remainingDays, 2);

        return response()->json([
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'leave_days' => $leaveDays,
            'leave_type' => $request->leave_type,
            'other_type' => $request->leave_type === 'other' ? $request->other_type : null,
            'remaining_days' => $remainingDays
        ], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date_format:Y-m-d H:i:s',
            'end_date' => 'required|date_format:Y-m-d H:i:s|after_or_equal:start_date',
            'leave_type' => 'required|in:paternity_leave,maternity_leave,sick_leave,vacation,travel_leave,other',
            'leave_days' => 'required|regex:/^\d+(\.\d{1})?$/',
            'other_type' => 'required_if:leave_type,other|string|max:255',
            'attachment' => 'required_if:leave_type,sick_leave|file',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $attachmentPath = null;
        if ($request->hasFile('attachment') && $request->leave_type == 'sick_leave') {
            $file = $request->file('attachment');
            $path = $file->store('attachments', 'public');
            $filename = basename($path);
            $attachmentPath = env('STORAGE') . '/attachments/' . $filename;
        }

        $leaveDays = $this->getWorkingDays($request->start_date, $request->end_date);
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
                'leave_days_requested' => $leaveDaysRequested,
                'effective_leave_days' => $effectiveLeaveDays,
                'attachment_path' => $attachmentPath,
                'status' => 'on_hold'
            ]);
        }

        $this->sendLeaveNotification(auth()->user(), $request->leave_type, $request->leave_type === 'other' ? $request->other_type : null);

        return response()->json([
            'message' => 'Leave request stored successfully!',
            'leave' => $leaveEntries,
        ], 201);
    }

    private function sendLeaveNotification($authUser, $leaveType, $otherType = null)
    {
        $title = 'New leave request';

        if ($leaveType === 'other' && $otherType) {
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

        foreach ($receivers as $receiver) {
            $notifications = Notification::create([
                'sender_id' => $authUser->id,
                'receiver_id' => $receiver->id,
                'title' => $title,
                'message' => $message,
            ]);
            broadcast(new NewNotificationEvent($notifications))->toOthers();
        }
    }

    private function calculateLeaveDaysRequested($leaveType, $leaveDays)
    {
        if ($leaveType == 'sick_leave') {
            return max(0, $leaveDays - 2);
        }

        return $leaveDays;
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

        $holidays = \App\Models\PublicHoliday::where(function($query) use ($start, $end) {
            $query->whereBetween('start_date', [$start->toDateString(), $end->toDateString()])
                ->orWhereBetween('end_date', [$start->toDateString(), $end->toDateString()])
                ->orWhere(function($query) use ($start, $end) {
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
            ->header('Content-Disposition', 'attachment; filename="leave_request_'.$leave->id.'.pdf"');
    }
}
