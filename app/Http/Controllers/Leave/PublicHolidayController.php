<?php

namespace App\Http\Controllers\Leave;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PublicHoliday;
use Carbon\Carbon;
use Exception;

class PublicHolidayController extends Controller
{
    public function index()
    {
        $holidays = PublicHoliday::orderBy('start_date', 'asc')->paginate(7);

        return response()->json([
            'data' => $holidays->items(),
            'meta' => [
                'current_page' => $holidays->currentPage(),
                'per_page' => $holidays->perPage(),
                'total_pages' => $holidays->lastPage(),
                'total_holidays' => $holidays->total(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
            ]);

            $startDate = Carbon::parse($validated['start_date']);
            $endDate = Carbon::parse($validated['end_date']);
            $numberOfDays = $startDate->diffInDays($endDate) + 1;

            $publicHoliday = PublicHoliday::create([
                'name' => $request->name,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'number_of_days' => $numberOfDays,
            ]);

            $this->updateLeavesForNewHoliday($publicHoliday);

            return response()->json([
                'message' => 'Public holiday added successfully!',
                'data' => $publicHoliday
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


    private function updateLeavesForNewHoliday($publicHoliday)
    {
        $affectedLeaves = \App\Models\Leave::where(function ($query) use ($publicHoliday) {
            $query->whereBetween('start_date', [$publicHoliday->start_date, $publicHoliday->end_date])
                ->orWhereBetween('end_date', [$publicHoliday->start_date, $publicHoliday->end_date])
                ->orWhere(function ($query) use ($publicHoliday) {
                    $query->where('start_date', '<=', $publicHoliday->start_date)
                        ->where('end_date', '>=', $publicHoliday->end_date);
                });
        })->get();

        foreach ($affectedLeaves as $leave) {
            $leaveDays = (new \App\Http\Controllers\Leave\LeaveController)->getWorkingDays($leave->start_date, $leave->end_date);
            $effectiveDays = $leave->leave_type == 'sick_leave'
                ? max(0, $leaveDays - 2)
                : $leaveDays;

            $leave->leave_days_requested = $leaveDays;
            $leave->effective_leave_days = $effectiveDays;
            $leave->save();
        }
    }


    public function update(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
            ]);

            $publicHoliday = PublicHoliday::findOrFail($id);

            $startDate = Carbon::parse($validated['start_date']);
            $endDate = Carbon::parse($validated['end_date']);
            $numberOfDays = $startDate->diffInDays($endDate) + 1;

            $publicHoliday->update([
                'name' => $request->name,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'number_of_days' => $numberOfDays,
            ]);

            $this->updateLeavesForNewHoliday($publicHoliday);
            return response()->json([
                'message' => 'Public holiday updated successfully!',
                'data' => $publicHoliday
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

}
