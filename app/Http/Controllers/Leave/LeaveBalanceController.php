<?php

namespace App\Http\Controllers\Leave;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Leave;
use App\Models\LeavesBalance;
use App\Models\FixedLeaves;
use App\Models\User;
use Carbon\Carbon;

class LeaveBalanceController extends Controller
{
    public function index(Request $request)
    {
        $search = trim($request->input('search'));
        $query = User::where('role', '!=', 'admin');

        if (!empty($search)) {
            if (str_contains($search, ' ')) {
                [$firstName, $lastName] = explode(' ', $search, 2);
                $query->where('first_name', 'LIKE', "%$firstName%")
                      ->where('last_name', 'LIKE', "%$lastName%");
            } else {
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'LIKE', "%$search%")
                      ->orWhere('last_name', 'LIKE', "%$search%");
                });
            }
        }

        $users = $query->select('id', 'first_name', 'last_name', 'avatar_path')
            ->orderBy('first_name', 'asc')
            ->paginate(6);

        $sickLeaveMax = FixedLeaves::where('leave_type', 'sick_leave')->value('max_days');

        $data = $users->map(function ($user) use ($sickLeaveMax) {
            $personalLeaveUsed = Leave::where('user_id', $user->id)
                ->whereIn('leave_type', ['personal_leave', 'other'])
                ->where('status', 'approved')
                ->sum('effective_leave_days');

            $personalLeaveBalance = LeavesBalance::where('user_id', $user->id)->sum('leave_day_limit');
            $personalLeaveRemaining = round($personalLeaveBalance - $personalLeaveUsed, 2);

            $currentYear = Carbon::now()->year;
            $sickLeaveEffectiveDays = Leave::where('user_id', $user->id)
                ->where('leave_type', 'sick_leave')
                ->whereYear('start_date', $currentYear)
                ->where('status', 'approved')
                ->sum('leave_days_requested');

            return [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'avatar_path' => $user->avatar_path ? asset($user->avatar_path) : null,
                'personal_leave_remaining' => $personalLeaveRemaining,
                'sick_leave_effective_days_current_year' => round($sickLeaveEffectiveDays, 2),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => [
                'current_page' => $users->currentPage(),
                'per_page' => $users->perPage(),
                'total_pages' => $users->lastPage(),
                'total_employees' => $users->total(),
            ],
        ]);
    }

    public function store(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);

            $validated = $request->validate([
                'leave_day_limit' => 'required|numeric|min:0.25',
                'description' => 'nullable|string|max:255',
            ], [
                'leave_day_limit.required' => 'The leave day limit is required.',
                'leave_day_limit.numeric' => 'The leave day limit must be a valid number.',
                'leave_day_limit.min' => 'The leave day limit must be at least 0.25.',
                'description.max' => 'The description may not be greater than 255 characters.',
            ]);

            $leave = LeavesBalance::create([
                'user_id' => $user->id,
                'leave_day_limit' => $validated['leave_day_limit'],
                'description' => $validated['description'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Leave balance added successfully!',
                'data' => $leave,
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $ve) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $ve->errors()
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while adding the leave balance.',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function show($userId)
    {
        try {
            $user = User::findOrFail($userId);

            $leaves = $user->leaveBalances()
                ->orderBy('created_at', 'desc')
                ->paginate(8);

            $totalLeaveDayLimit = $user->leaveBalances()->sum('leave_day_limit');

            return response()->json([
                'success' => true,
                'data' => $leaves->items(),
                'meta' => [
                    'current_page' => $leaves->currentPage(),
                    'per_page' => $leaves->perPage(),
                    'total_pages' => $leaves->lastPage(),
                    'total_leaves' => $leaves->total(),
                ],
                'user' => [
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                ],
                'total_leave_day_limit' => $totalLeaveDayLimit,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
                'details' => $e->getMessage(),
            ], 404);
        }
    }

    public function destroy($id)
    {
        try {
            $leave = LeavesBalance::where('id', $id)->lockForUpdate()->firstOrFail();
            $leave->delete();

            return response()->json([
                'success' => true,
                'message' => 'Leave balance deleted successfully!'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting the leave balance.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }
}
