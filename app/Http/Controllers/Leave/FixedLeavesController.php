<?php

namespace App\Http\Controllers\Leave;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FixedLeaves;
use Illuminate\Database\QueryException;

class FixedLeavesController extends Controller
{

    public function index()
    {
        try {
            $limits = FixedLeaves::select('id', 'leave_type', 'max_days')->get();
            return response()->json($limits, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to retrieve specific leave types.'], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'leave_type' => 'required|in:paternity_leave,maternity_leave,sick_leave',
                'max_days' => 'required|integer|min:0',
            ]);

            $existingLeave = FixedLeaves::where('leave_type', $request->leave_type)->first();
            if ($existingLeave) {
                return response()->json(['message' => 'This leave type already exists.'], 400);
            }

            FixedLeaves::create([
                'leave_type' => $request->leave_type,
                'max_days' => $request->max_days
            ]);

            return response()->json(['message' => 'Specific leave added successfully.'], 201);
        } catch (\Illuminate\Validation\ValidationException $ve) {
            return response()->json($ve->errors(), 422);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Unexpected error: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'max_days' => 'required|integer|min:0',
            ]);

            $leaveLimit = FixedLeaves::findOrFail($id);
            $leaveLimit->update(['max_days' => $request->max_days]);

            return response()->json(['message' => 'Leave updated successfully.'], 200);
        } catch (\Illuminate\Validation\ValidationException $ve) {
            return response()->json($ve->errors(), 422);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update leave: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $leaveLimit = FixedLeaves::findOrFail($id);
            $leaveLimit->delete();

            return response()->json(['message' => 'Leave deleted successfully.'], 200);
        } catch (\Illuminate\Validation\ValidationException $ve) {
            return response()->json($ve->errors(), 422);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete leave.'], 500);
        }
    }
}
