<?php

namespace App\Http\Controllers\Authentificate;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login']]);
    }

    public function login(Request $request)
    {
        $credentials = $request->only('username', 'password');
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $key = 'login_attempts_' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'error' => "Too many attempts. Please try again in $seconds seconds."
            ], 429);
        }

        $user = \App\Models\User::where('username', $credentials['username'])->first();
        if (!$user) {
            RateLimiter::hit($key, 300);
            return response()->json(['error' => 'Invalid username.'], 401);
        }
        if (!Hash::check($credentials['password'], $user->password)) {
            RateLimiter::hit($key, 300);
            return response()->json(['error' => 'Invalid password.'], 401);
        }
        if (!$token = auth()->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $role = $user->role;
        $id=$user->id;
        RateLimiter::clear($key);

        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 1440,
            'id'=>$id,
            'role' => $role,
            'gender'=> $user->gender,
            'company'=>$user->company,

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
