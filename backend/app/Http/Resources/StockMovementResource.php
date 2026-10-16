<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockMovementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_name' => $this->whenLoaded('product', fn () => $this->product->name),
            'warehouse_id' => $this->warehouse_id,
            'warehouse_name' => $this->whenLoaded('warehouse', fn () => $this->warehouse->name),
            'user_name' => $this->whenLoaded('user', fn () => $this->user?->name),
            'type' => $this->type,
            'quantity' => $this->quantity,
            'balance_after' => $this->balance_after,
            'reference' => $this->reference,
            'note' => $this->note,
            'created_at' => $this->created_at,
        ];
    }
}
