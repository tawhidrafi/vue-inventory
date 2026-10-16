<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('product')?->id;

        return [
            'sku' => ['nullable', 'string', 'max:255', Rule::unique('products', 'sku')->ignore($id)],
            'barcode' => ['nullable', 'string', 'max:255', Rule::unique('products', 'barcode')->ignore($id)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'unit' => ['required', 'string', 'max:20'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'sell_price' => ['nullable', 'numeric', 'min:0'],
            'reorder_level' => ['nullable', 'integer', 'min:0'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'is_active' => ['boolean'],
        ];
    }
}
