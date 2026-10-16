<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::query()
            ->when($request->search, function ($q, $s) {
                $q->where(function ($q) use ($s) {
                    $q->where('name', 'like', "%{$s}%")
                        ->orWhere('email', 'like', "%{$s}%");
                });
            })
            ->orderBy('name')
            ->paginate($request->integer('per_page', 10));

        return UserResource::collection($users);
    }

    public function store(UserRequest $request)
    {
        $user = User::create($request->validated());

        return (new UserResource($user))->response()->setStatusCode(201);
    }

    public function update(UserRequest $request, User $user)
    {
        $data = $request->validated();

        // an empty password means "leave it unchanged"
        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        if ($user->role === 'admin' && $data['role'] !== 'admin' && User::where('role', 'admin')->count() <= 1) {
            return response()->json([
                'message' => 'At least one admin must remain.',
            ], 422);
        }

        $user->update($data);

        return new UserResource($user);
    }

    public function destroy(Request $request, User $user)
    {
        if ($request->user()->is($user)) {
            return response()->json([
                'message' => 'You cannot delete your own account.',
            ], 422);
        }

        if ($user->role === 'admin' && User::where('role', 'admin')->count() <= 1) {
            return response()->json([
                'message' => 'At least one admin must remain.',
            ], 422);
        }

        $user->delete();

        return response()->noContent();
    }
}
