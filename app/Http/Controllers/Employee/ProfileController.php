<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\User;

class ProfileController extends Controller
{
    public function showsidebar()
    {
        $user = Auth::user();

        return response()->json([
            'avatar_path' => asset($user->avatar_path),
            'full_name' => "{$user->first_name} {$user->last_name}"
        ]);
    }

    public function show()
    {
        $user = Auth::user();

        return response()->json([
            'avatar_path' => asset($user->avatar_path),
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'gender' => $user->gender,
            'email' => $user->email,
            'phone' => $user->phone,
            'address' => $user->address,
            'company' => $user->company,
            'job_description' => $user->job_description,
            'start_date' => $user->start_date,
        ]);
    }
    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        
        try {
            $validated = $request->validate([
                'email' => 'required|email|unique:users,email,' . $user->id,
                'phone' => 'nullable|string|max:12',
                'address' => 'nullable|string|max:255',
            ], [
                'email.required' => 'The email is required.',
                'email.email' => 'The email must be valid.',
                'email.unique' => 'This email is already taken.',
            ]);

            $user->email = $validated['email'];
            $user->phone = $validated['phone'] ?? $user->phone;
            $user->address = $validated['address'] ?? $user->address;
            $user->save();

            return response()->json([
                'message' => 'Profile updated successfully!'
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $ve) {
            return response()->json($ve->errors(), 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An unexpected error occurred.',
                'details' => $e->getMessage()
            ], 500);
        }
    }
    public function updateAvatar(Request $request)
    {
        $user = Auth::user();
        
        try {
            $validated = $request->validate([
                'avatar_path' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            ], [
                'avatar_path.image' => 'The file must be an image.',
                'avatar_path.mimes' => 'Only JPEG, PNG, JPG, and GIF are allowed.',
                'avatar_path.max' => 'The image must not exceed 2MB.',
            ]);

            if ($request->hasFile('avatar_path')) {
                if ($user->avatar_path) {
                    $oldAvatarPath = str_replace('/storage/', 'public/', $user->avatar_path);
                    Storage::delete($oldAvatarPath);
                }

                $avatar = $request->file('avatar_path');
                $avatarName = uniqid() . '_' . $avatar->getClientOriginalName();
                $path = $avatar->storeAs('avatars', $avatarName, 'public');
                $user->avatar_path = '/storage/avatars/' . $avatarName;
                $user->save();
            }

            return response()->json([
                'message' => 'Avatar updated successfully!',
                'avatar_path' => asset($user->avatar_path),
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $ve) {
            return response()->json($ve->errors(), 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An unexpected error occurred.',
                'details' => $e->getMessage()
            ], 500);
        }
    }
}
