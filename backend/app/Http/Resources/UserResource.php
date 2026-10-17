<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'notify_low_stock' => (bool) $this->notify_low_stock,
            'notify_purchase_orders' => (bool) $this->notify_purchase_orders,
            'created_at' => $this->created_at,
        ];
    }
}
