<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'status' => $this->status,
            'customer_id' => $this->customer_id,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'order_date' => $this->order_date,
            'expected_ship_date' => $this->expected_ship_date,
            'shipped_at' => $this->shipped_at,
            'shipping_address' => $this->shipping_address,
            'notes' => $this->notes,
            'subtotal' => $this->subtotal,
            'tax' => $this->tax,
            'discount' => $this->discount,
            'total' => $this->total,
            'items' => SalesOrderItemResource::collection($this->whenLoaded('items')),
            'items_count' => $this->whenCounted('items'),
            'creator' => new UserResource($this->whenLoaded('creator')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
