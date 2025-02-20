<?php

namespace App\Http\Controllers\Leave;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\LeaveBalance;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LeaveBalanceController extends Controller
{
    public function index(Request $request)
    {
        $search = trim($request->input('search'));
        $query = User::where('role', '!=', 'admin')
                        ->where('id', '!=', Auth::id());

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

        return response()->json([
            'data' => $users->makeHidden('avatar_path')->map(function ($user) {
                return [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'avatar_path' => $user->avatar_path,
                ];
            }),
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
            $user = User::where('id', $id)->firstOrFail();
            $validated = $request->validate([
                'leave_day_limit' => 'required|numeric|min:0',
                'description' => 'nullable|string|max:255',
            ], [
                'leave_day_limit.required' => 'The leave day limit is required.',
                'leave_day_limit.numeric' => 'The leave day limit must be a valid number.',
                'leave_day_limit.min' => 'The leave day limit must be at least 0.',
                'description.max' => 'The description may not be greater than 255 characters.',
            ]);
            $leave = LeaveBalance::create([
                'user_id' => $user->id,
                'leave_day_limit' => $validated['leave_day_limit'],
                'description' => $validated['description'],
            ]);

            return response()->json([
                'message' => 'Leave balance added successfully!',
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $ve) {
            return response()->json($ve->errors(), 422);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred while adding the leave balance.'], 500);
        }
    }


    public function show($userId)
    {
        $user = User::findOrFail($userId);

        $leaves = $user->leaveBalances()
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json([
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
            ]
        ]);
    }

    public function destroy($id)
    {
        try {
            $leave = LeaveBalance::where('id', $id)->lockForUpdate()->firstOrFail();
    
            $leave->delete();
    
            return response()->json([
                'message' => 'Leave balance deleted successfully!'
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An error occurred while deleting the leave balance.'
            ], 500);
        }
    }
    
}
