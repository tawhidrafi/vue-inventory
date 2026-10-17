<?php

namespace App\Http\Resources;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Supplier
 */
class ProductSupplierResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'supplier_sku' => $this->pivot->supplier_sku,
            'unit_cost' => $this->pivot->unit_cost,
            'lead_time_days' => $this->pivot->lead_time_days,
            'min_order_quantity' => $this->pivot->min_order_quantity,
            'is_preferred' => (bool) $this->pivot->is_preferred,
        ];
    }
}
