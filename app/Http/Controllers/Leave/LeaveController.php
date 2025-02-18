<?php

namespace App\Http\Controllers\Leave;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Leave;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class LeaveController extends Controller
{
    public function store(Request $request) {
        $validatedData = $this->validateLeaveRequest($request);
        if ($validatedData instanceof \Illuminate\Http\JsonResponse) {
            return $validatedData;
        }
        
        $leaveRecords = $this->storeLeave($validatedData);
        return response()->json([ 'message' => 'Leave request submitted successfully!'], 201);
    }

    private function validateLeaveRequest(Request $request) {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'leave_days_requested' => 'required|numeric|min:1',
            'leave_days_current_year' => 'nullable|numeric|min:0',
            'leave_days_next_year' => 'nullable|numeric|min:0',
            'reason' => 'required|in:vacation,travel_leave,paternity_leave,maternity_leave,sick_leave,other',
            'other_reason' => 'nullable|required_if:reason,other|string|max:255',
            'attachment' => 'nullable|required_if:reason,sick_leave|mimes:pdf,jpg,jpeg|max:2048',
        ],[
            'start_date.required' => 'The start date field is required.',
            'start_date.date' => 'The start date field must be a valid date.',
            'end_date.required' => 'The end date field is required.',
            'end_date.date' => 'The end date field must be a valid date.',
            'end_date.after_or_equal' => 'The end date must be equal to or later than the start date.',
            'leave_days_requested.required' => 'The number of days requested is required.',
            'leave_days_current_year.numeric' => 'The number of days for the current year must be a number.',
            'leave_days_current_year.min' => 'The number of days for the current year cannot be negative.',
            'leave_days_next_year.numeric' => 'The number of days for the next year must be a number.',
            'leave_days_next_year.min' => 'The number of days for the next year cannot be negative.',
            'reason.required' => 'The leave type is required.',
            'reason.in' => 'The selected leave type is invalid.',
            'other_reason.required_if' => 'The "Other reason" field is required if the leave type is "other".',
            'attachment.required_if' => 'An attachment is required for sick leave.',
            'attachment.mimes' => 'The attachment must be a file of type PDF, JPG, or JPEG.',

        ]);
        

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->all();
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $path = $file->store('public/attachments');
            $filename = basename($path);
            $data['attachment_path'] = env('STORAGE').'/attachments/'.$filename;
        }
        return $data;
    }

    private function storeLeave($data) {
        $startYear = date('Y', strtotime($data['start_date']));
        $endYear = date('Y', strtotime($data['end_date']));
        
        $records = [];
    
        if ($startYear !== $endYear) {
            $currentYearDays = $data['leave_days_current_year'] ?? 0;
            $nextYearDays = $data['leave_days_next_year'] ?? 0;
    
            $records[] = $this->createLeave($data, $data['start_date'], "$startYear-12-31", $currentYearDays);
            $records[] = $this->createLeave($data, "$endYear-01-01", $data['end_date'], $nextYearDays);
        } else {
            $records[] = $this->createLeave($data, $data['start_date'], $data['end_date'], $data['leave_days_requested']);
        }
    
        return $records;
    }
    

    private function createLeave($data, $startDate, $endDate, $days) {
        return Leave::create([
            'user_id' => Auth::id(),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'leave_days_requested' => $days,
            'effective_leave_days' => $data['reason'] === 'sick_leave' ? max(0, $days - 2) : 0,
            'reason' => $data['reason'],
            'other_reason' => $data['other_reason'] ?? null,
            'attachment_path' => $data['attachment_path'] ?? null,
        ]);
    }
}
