<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 422);
        }

        $plain = Str::random(64);

        $user->apiTokens()->create([
            'token' => hash('sha256', $plain),
            'expires_at' => now()->addDays(7),
        ]);

        return response()->json([
            'token' => $plain,   // shown only once, store it in the frontend
            'user' => $user,
        ]);
    }

    public function logout(Request $request)
    {
        $request->attributes->get('api_token')->delete();

        return response()->noContent();
    }

    public function me(Request $request)
    {
        return $request->user();
    }
}
