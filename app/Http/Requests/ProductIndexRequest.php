<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductIndexRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
{
    return [
        'search' => 'nullable|string|max:255',
        'category_id' => 'nullable|exists:categories,id',
        'min_price' => 'nullable|numeric|min:0',
        'max_price' => 'nullable|numeric|min:0',
        'page' => 'nullable|integer|min:1'
    ];
}
}
