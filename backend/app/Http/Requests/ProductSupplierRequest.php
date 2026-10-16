<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // when updating, the supplier comes from the route
        $isUpdate = (bool) $this->route('supplier');

        return [
            'supplier_id' => [$isUpdate ? 'nullable' : 'required', 'integer', 'exists:suppliers,id'],
            'supplier_sku' => ['nullable', 'string', 'max:255'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'lead_time_days' => ['nullable', 'integer', 'min:0'],
            'min_order_quantity' => ['nullable', 'integer', 'min:0'],
            'is_preferred' => ['boolean'],
        ];
    }
}
