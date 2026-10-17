<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     * Update the signed-in user's own notification preferences.
     */
    public function updatePreferences(Request $request)
    {
        $data = $request->validate([
            'notify_low_stock' => ['sometimes', 'boolean'],
            'notify_purchase_orders' => ['sometimes', 'boolean'],
        ]);

        $user = $request->user();
        $user->update($data);

        return new UserResource($user->fresh());
    }
}
