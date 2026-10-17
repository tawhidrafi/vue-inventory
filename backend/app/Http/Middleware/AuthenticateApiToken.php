<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $plain = $request->bearerToken();

        if (! $plain) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $token = ApiToken::with('user')
            ->where('token', hash('sha256', $plain))
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->first();

        if (! $token) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $token->forceFill(['last_used_at' => now()])->save();

        Auth::setUser($token->user);
        $request->setUserResolver(fn () => $token->user);
        $request->attributes->set('api_token', $token);

        return $next($request);
    }
}
