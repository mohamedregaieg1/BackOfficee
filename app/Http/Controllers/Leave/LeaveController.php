<?php

namespace App\Http\Controllers\Leave;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Leave;
use App\Models\FixedLeaves;
use App\Models\LeavesBalance;
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
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'leave_type' => 'required|in:paternity_leave,maternity_leave,sick_leave,vacation,travel_leave,other',
            'other_type' => 'required_if:leave_type,other',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $leaveDays = $this->getWorkingDays($request->start_date, $request->end_date);

        return response()->json([
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'leave_days' => $leaveDays,
            'leave_type' => $request->leave_type,
            'other_type' => $request->leave_type === 'other' ? $request->other_type : null
        ], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'leave_type' => 'required|in:paternity_leave,maternity_leave,sick_leave,vacation,travel_leave,other',
            'leave_days' => 'required|integer|min:1',
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

        $leave = Leave::create([
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

        $user = Auth::user();
        $remainingDays = $this->getRemainingLeaveDays($user->id, $request->leave_type, $effectiveLeaveDays);

        return response()->json([
            'message' => 'Leave request stored successfully!',
            'leave' => $leave,
            'remaining_days' => $remainingDays
        ], 201);
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
                $adjustedUsedDays = Leave::where('user_id', $userId)
                    ->where('leave_type', 'sick_leave')
                    ->whereIn('status', ['on_hold', 'approved'])
                    ->sum(\DB::raw('leave_days_requested - effective_leave_days'));
    
                $remainingDays = $fixedLeave->max_days - $adjustedUsedDays;
    
                return  $remainingDays;
            }
    
            if ($leaveType === 'paternity_leave') {
                $usedDays = Leave::where('user_id', $userId)
                    ->where('leave_type', 'paternity_leave')
                    ->whereIn('status', ['on_hold', 'approved'])
                    ->sum('leave_days_requested');
    
                $remainingDays = $fixedLeave->max_days - $usedDays;
    
                return  $remainingDays;
            }
    
            if ($leaveType === 'maternity_leave') {
                $usedDays = Leave::where('user_id', $userId)
                    ->where('leave_type', 'maternity_leave')
                    ->whereIn('status', ['on_hold', 'approved'])
                    ->sum('leave_days_requested');
    
                $remainingDays = $fixedLeave->max_days - $usedDays;
    
                return  $remainingDays;
            }
        } else {
            $leaveBalance = LeavesBalance::where('user_id', $userId)->first();
    
            if (!$leaveBalance) {
                return 0;
            }
    
            $usedDays = Leave::where('user_id', $userId)
                ->whereIn('leave_type', ['vacation', 'travel_leave', 'other'])
                ->whereIn('status', ['on_hold', 'approved'])
                ->sum('effective_leave_days');
            
            $remainingDays = $leaveBalance->leave_day_limit - $usedDays;
    
            return  $remainingDays;
        }
    }

    private function getWorkingDays($startDate, $endDate)
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        $workingDays = 0;

        while ($start <= $end) {
            if (!$start->isWeekend()) {
                $workingDays++;
            }
            $start->addDay();
        }

        return $workingDays;
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
