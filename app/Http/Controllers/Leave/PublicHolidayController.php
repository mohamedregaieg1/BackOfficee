<?php

namespace App\Http\Controllers\Leave;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PublicHoliday;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Exception;

class PublicHolidayController extends Controller
{

    public function getAvailableHolidayYears()
    {
        $years = DB::table('public_holidays')
            ->selectRaw('DISTINCT YEAR(start_date) as year')
            ->orderBy('year', 'desc')
            ->pluck('year');

        return response()->json($years);
    }
    /**
     * List public holidays paginated.
     * Returns JSON with data and pagination metadata.
     */
    public function index(Request $request)
    {
        $query = PublicHoliday::query();

        if ($request->has('name') && !empty($request->name)) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }
        if ($request->has('year') && !empty($request->year)) {
            $query->whereYear('start_date', $request->year);
        }

        $holidays = $query->orderBy('start_date', 'asc')->paginate(7);

        return response()->json([
            'success' => true,
            'data' => $holidays->items(),
            'meta' => [
                'current_page' => $holidays->currentPage(),
                'per_page' => $holidays->perPage(),
                'total_pages' => $holidays->lastPage(),
                'total_holidays' => $holidays->total(),
            ],
        ]);
    }

    /**
     * Create a new public holiday.
     * Validates input and calculates number_of_days.
     * Updates related leaves after creation.
     *
     * Returns success message with created holiday data.
     * Errors:
     * - 422 for validation errors (with validation messages)
     * - 500 for unexpected errors (with error details)
     */
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
                'name' => $validated['name'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'number_of_days' => $numberOfDays,
            ]);

            $this->updateLeavesForNewHoliday($publicHoliday);

            return response()->json([
                'success' => true,
                'message' => 'Public holiday added successfully.',
                'data' => $publicHoliday
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $ve) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $ve->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Updates leaves overlapping the new/updated public holiday.
     * Recalculates leave days and effective days for each affected leave.
     *
     */
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

    private function restoreLeaveDaysForDeletedHoliday(PublicHoliday $holiday)
    {
        $affectedLeaves = \App\Models\Leave::where(function ($query) use ($holiday) {
            $query->whereBetween('start_date', [$holiday->start_date, $holiday->end_date])
                ->orWhereBetween('end_date', [$holiday->start_date, $holiday->end_date])
                ->orWhere(function ($query) use ($holiday) {
                    $query->where('start_date', '<=', $holiday->start_date)
                        ->where('end_date', '>=', $holiday->end_date);
                });
        })->get();

        foreach ($affectedLeaves as $leave) {
            $leaveDays = (new \App\Http\Controllers\Leave\LeaveController)->getWorkingDays($leave->start_date, $leave->end_date);
            $holidayDays = Carbon::parse($holiday->start_date)->diffInDays(Carbon::parse($holiday->end_date)) + 1;

            $newLeaveDays = $leaveDays + $holidayDays;
            $effectiveDays = $leave->leave_type === 'sick_leave'
                ? max(0, $newLeaveDays - 2)
                : $newLeaveDays;

            $leave->leave_days_requested = $newLeaveDays;
            $leave->effective_leave_days = $effectiveDays;
            $leave->save();
        }
    }

    public function destroy($id)
    {
        try {
            $publicHoliday = PublicHoliday::findOrFail($id);

            $this->restoreLeaveDaysForDeletedHoliday($publicHoliday);

            $publicHoliday->delete();

            return response()->json([
                'success' => true,
                'message' => 'Public holiday deleted successfully.'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Public holiday not found.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred.',
                'details' => $e->getMessage()
            ], 500);
        }
    }

}
