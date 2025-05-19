<?php

namespace App\Http\Middleware;

use Closure;
use Tymon\JWTAuth\Facades\JWTAuth;

class VerifyTokenVersion
{
    public function handle($request, Closure $next)
    {
        $user = JWTAuth::parseToken()->authenticate();
        $payload = JWTAuth::parseToken()->getPayload();

        if ($user->token_version !== $payload->get('token_version')) {
            return response()->json([
                'success' => false,
                'message' => 'Votre session a expiré. Veuillez vous reconnecter.'
            ], 401);
        }

        return $next($request);
    }
}
