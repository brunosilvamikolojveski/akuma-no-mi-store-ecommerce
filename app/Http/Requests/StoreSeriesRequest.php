<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSeriesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:180'],
            'slug' => ['required', 'string', 'max:200', Rule::unique('series', 'slug')],
            'publisher_id' => ['nullable', 'integer', 'exists:publishers,id'],
            'description' => ['nullable', 'string'],
            'status' => ['sometimes', 'string', 'max:30'],
            'author_ids' => ['sometimes', 'array'],
            'author_ids.*' => ['integer', 'distinct', 'exists:authors,id'],
            'category_ids' => ['sometimes', 'array'],
            'category_ids.*' => ['integer', 'distinct', 'exists:categories,id'],
        ];
    }
}
