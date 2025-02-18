<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class ProfileController extends Controller
{
    public function show()
    {
        $user = Auth::user();
        return response()->json([
            'avatar_path' => $user->avatar_path,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'gender' => $user->gender,
            'email' => $user->email,
            'company' => $user->company,
            'job_description' => $user->job_description,
            'start_date' => $user->start_date,
        ]);
    }
}
