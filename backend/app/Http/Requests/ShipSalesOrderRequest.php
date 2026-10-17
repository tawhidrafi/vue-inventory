<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShipSalesOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'items' => ['nullable', 'array'],
            'items.*.id' => ['required_with:items', 'integer', 'exists:sales_order_items,id'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:0'],
        ];
    }
}
