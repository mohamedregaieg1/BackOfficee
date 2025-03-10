<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;


class UserController extends Controller
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
    
        $users = $query->select('id', 'first_name', 'last_name', 'email', 'phone','company', 'role', 'start_date', 'avatar_path','job_description')
                       ->orderBy('first_name', 'asc')
                       ->paginate(6);
    
        return response()->json([
            'data' => $users->makeHidden('avatar_path')->map(function ($user) {
                return [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'company' => $user->company,
                    'role' => $user->role,
                    'start_date' => $user->start_date->format('Y-m-d'),
                    'job_description' => $user->job_description,
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
    

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'gender' => 'required|in:male,female',
                'username' => 'required|string|unique:users,username',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:6',
                'company' => 'required|in:adequate,procan',
                'start_date' => 'required|date',
                'role' => 'required|in:employee,hr',
                'job_description' => 'required|string|max:20',
            ], [
                'first_name.required' => 'The first name is required.',
                'last_name.required' => 'The last name is required.',
                'gender.required' => 'The gender is required.',
                'username.required' => 'The username is required.',
                'username.unique' => 'This username is already taken.',
                'email.required' => 'The email is required.',
                'email.email' => 'The email must be a valid email address.',
                'email.unique' => 'This email is already registered.',
                'password.required' => 'The password is required.',
                'password.min' => 'The password must be at least 6 characters.',
                'company.required' => 'The company is required.',
                'start_date.required' => 'The start date is required.',
                'role.required' => 'The role is required.',
                'job_description.required' => 'The job description is required.',
                'job_description.max' => 'The job description must not exceed 20 characters.',
            ]);

            // Déterminer le chemin de l'avatar par défaut
            $avatarFileName = $validated['gender'] === 'female' ? 'avatarfemale.png' : 'avatarmale.png';
            $avatarPath = asset('/dist/img/' . $avatarFileName); // L'URL publique ne peut pas être nulle
            
            User::create([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'gender' => $validated['gender'],
                'avatar_path' => $avatarPath,
                'username' => $validated['username'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'company' => $validated['company'],
                'start_date' => $validated['start_date'],
                'role' => $validated['role'],
                'job_description' => $validated['job_description'],
            ]);

            return response()->json([
                'message' => 'User created successfully!'
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $ve) {
            return response()->json($ve->errors(), 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An unexpected error occurred.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }
 
    public function update(Request $request, $id)
    {
        try {
            $user = User::where('id', $id)->lockForUpdate()->firstOrFail();

            $validated = $request->validate([
                'job_description' => 'required|string|max:15',
                'company' => 'required|in:adequate,procan',
                'role' => 'required|in:employee,hr',
            ], [
                'job_description.required' => 'The job description is required.',
                'job_description.max' => 'The job description must not exceed 20 characters.',
                'company.required' => 'The company is required.',
                'role.required' => 'The role is required.',
                ]);

            $user->update([
                'job_description' => $validated['job_description'],
                'company' => $validated['company'],
                'role' => $validated['role'],
            ]);

            return response()->json([
                'message' => 'User updated successfully!'
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

    public function destroy($id)
    {
        try {
            $user = User::where('id', $id)->lockForUpdate()->firstOrFail();

            $user->delete();

            return response()->json([
                'message' => 'User successfully deleted!',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An unexpected error has occurred.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }
}