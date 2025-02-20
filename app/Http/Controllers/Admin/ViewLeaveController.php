<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Leave;

class ViewLeaveController extends Controller
{
    public function showLeaves($userId)
    {
        $user = User::findOrFail($userId);

        $leaves = Leave::where('user_id', $userId)
                    ->select('id', 'start_date', 'end_date', 'reason', 'other_reason', 'status')
                    ->paginate(10);

        return response()->json([
            'full_name' => "{$user->first_name} {$user->last_name}",
            'data' => $leaves->items(),
            'meta' => [
                'current_page' => $leaves->currentPage(),
                'per_page' => $leaves->perPage(),
                'total_pages' => $leaves->lastPage(),
                'total_leaves' => $leaves->total(),
            ],
        ]);
    }


    public function updateStatus(Request $request, $leaveId)
    {
        $validated = $request->validate([
            'status' => 'required|in:accepted,rejected,on hold',
        ]);

        $leave = Leave::findOrFail($leaveId);
        $leave->status = $validated['status'];
        $leave->save();

        return response()->json(['message' => 'Leave status updated successfully!']);
    }


}
