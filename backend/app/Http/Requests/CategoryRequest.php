<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // permissions come later
    }

    public function rules(): array
    {
        $id = $this->route('category')?->id;

        $parentRules = ['nullable', 'integer', 'exists:categories,id'];
        if ($id) {
            $parentRules[] = Rule::notIn([$id]); // a category can't be its own parent
        }

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('categories', 'name')->ignore($id)],
            'parent_id' => $parentRules,
            'description' => ['nullable', 'string'],
        ];
    }
}
