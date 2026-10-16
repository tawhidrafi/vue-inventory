<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReceivePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'update_cost' => ['boolean'],
            'items' => ['nullable', 'array'],
            'items.*.id' => ['required_with:items', 'integer', 'exists:purchase_order_items,id'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:0'],
        ];
    }
}
