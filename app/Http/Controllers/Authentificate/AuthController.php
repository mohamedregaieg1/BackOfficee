<?php

namespace App\Http\Controllers\Authentificate;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login']]);
    }

    public function login(Request $request)
    {
        $credentials = $request->only('username', 'password');

        // Validation avec format d’erreur uniforme
        $validator = \Validator::make($credentials, [
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Les champs requis sont manquants ou invalides.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $key = 'login_attempts_' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'success' => false,
                'message' => "Trop de tentatives. Réessayez dans $seconds secondes."
            ], 429);
        }

        $user = User::where('username', $credentials['username'])->first();
        if (!$user) {
            RateLimiter::hit($key, 300);
            return response()->json([
                'success' => false,
                'message' => 'Nom d’utilisateur invalide.'
            ], 401);
        }

        if (!Hash::check($credentials['password'], $user->password)) {
            RateLimiter::hit($key, 300);
            return response()->json([
                'success' => false,
                'message' => 'Mot de passe invalide.'
            ], 401);
        }

        if (!$token = auth()->attempt($credentials)) {
            return response()->json([
                'success' => false,
                'message' => 'Non autorisé.'
            ], 401);
        }

        RateLimiter::clear($key);

        return response()->json([
            'success' => true,
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 1440,
            'id' => $user->id,
            'role' => $user->role,
            'gender' => $user->gender,
            'company' => $user->company,
            'message' => 'Connexion réussie.'
        ]);
    }


    /**
     * Get the authenticated user.
     */
    public function me()
    {
        return response()->json(auth()->user());
    }

    /**
     * Log out the user (invalidate the token).
     */
    public function logout()
    {
        auth()->logout();
        return response()->json(['message' => 'Successfully logged out.']);
    }

    /**
     * Refresh a token.
     */
    public function refresh()
    {
        return response()->json([
            'access_token' => auth()->refresh(),
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
        ]);
    }
}
