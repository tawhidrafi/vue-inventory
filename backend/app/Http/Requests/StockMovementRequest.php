<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StockMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'type' => ['required', Rule::in(['in', 'out', 'adjustment'])],
            'quantity' => ['required', 'integer', 'not_in:0'],
            'note' => ['required_if:type,adjustment', 'nullable', 'string'],
        ];
    }

    public function after(): array
    {
        return [
            function ($validator) {
                $type = $this->input('type');
                $quantity = (int) $this->input('quantity');

                // 'in' and 'out' carry a magnitude; only adjustments may be signed.
                if (in_array($type, ['in', 'out'], true) && $quantity < 1) {
                    $validator->errors()->add('quantity', 'Quantity must be at least 1 for this movement type.');
                }
            },
        ];
    }
}
