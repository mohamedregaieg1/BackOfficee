<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\QueryException;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::where('role', '!=', 'admin')
            ->select('first_name', 'last_name', 'email', 'company', 'role', 'start_date', 'initial_leave_balance')
            ->orderBy('first_name', 'asc')
            ->paginate(6);
        $data = $users->items();
        $meta = [
            'current_page' => $users->currentPage(),
            'per_page' => $users->perPage(),
            'total_pages' => $users->lastPage(),
            'total_employees' => $users->total(),
        ];
        return response()->json([
            'data' => $data,
            'meta' => $meta,
        ]);
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'sex' => 'required|in:male,female',
                'username' => 'required|string|unique:users,username',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|confirmed|min:8',
                'company' => 'required|in:adequat,procan',
                'start_date' => 'required|date',
                'role' => 'required|in:employe,rh',
                'initial_leave_balance' => 'required|numeric|min:0',
            ], [
                // Messages d'erreur
                'first_name.required' => 'First name is required.',
                'last_name.required' => 'Last name is required.',
                'username.unique' => 'Username is already taken.',
                'email.unique' => 'Email is already registered.',
                'password.confirmed' => 'Passwords do not match.',
                'start_date.date' => 'Start date must be a valid date.',
                'initial_leave_balance.numeric' => 'Leave balance must be a numeric value.',
                'initial_leave_balance.min' => 'Leave balance cannot be negative.',
            ]);
            $avatarFileName = $validated['sex'] === 'female' ? 'avatarfemale.png' : 'avatarmale.png';
            $avatarPath = asset('storage/dist/img/' . $avatarFileName); // Génère une URL publique
            $user = User::create([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'sex' => $validated['sex'],
                'avatar_path' => $avatarPath,
                'username' => $validated['username'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'company' => $validated['company'],
                'start_date' => $validated['start_date'],
                'role' => $validated['role'],
                'initial_leave_balance' => $validated['initial_leave_balance'],
            ]);
            return response()->json([
                'message' => 'User created successfully!',
                'user' => $user,
            ], 201);

        } catch (QueryException $qe) {
            // Gestion des erreurs liées à la base de données
            return response()->json([
                'error' => 'A database error occurred.',
                'details' => $qe->getMessage(),
            ], 500);
        } catch (\Illuminate\Validation\ValidationException $ve) {
            // Gestion des erreurs de validation
            return response()->json([
                'error' => 'Validation failed.',
                'details' => $ve->errors(),
            ], 422);
        } catch (\Exception $e) {
            // Gestion des erreurs générales
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
                'username' => 'required|string|unique:users,username,' . $id,
                'company' => 'required|in:adequat,procan',
                'role' => 'required|in:employe,rh',
            ], [
                'username.required' => 'Le nom d\'utilisateur est obligatoire.',
                'username.unique' => 'Ce nom d\'utilisateur est déjà pris.',
                'company.required' => 'Le champ "company" est requis.',
                'role.required' => 'Le rôle est requis.',
            ]);
    
            $user->update([
                'username' => $validated['username'],
                'company' => $validated['company'],
                'role' => $validated['role'],
            ]);
    
            return response()->json([
                'message' => 'Utilisateur mis à jour avec succès!',
                'user' => $user,
            ]);
        } catch (\Illuminate\Validation\ValidationException $ve) {
            return response()->json([
                'error' => 'La validation a échoué.',
                'details' => $ve->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Une erreur inattendue est survenue.',
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
                'message' => 'Utilisateur supprimé avec succès!',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Une erreur inattendue est survenue.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

}

