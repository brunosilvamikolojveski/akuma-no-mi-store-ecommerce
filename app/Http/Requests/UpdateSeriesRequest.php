<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSeriesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $seriesId = $this->route('series');

        return [
            'title' => ['sometimes', 'required', 'string', 'max:180'],
            'slug' => ['sometimes', 'required', 'string', 'max:200', Rule::unique('series', 'slug')->ignore($seriesId)],
            'publisher_id' => ['sometimes', 'nullable', 'integer', 'exists:publishers,id'],
            'description' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', 'string', 'max:30'],
            'author_ids' => ['sometimes', 'array'],
            'author_ids.*' => ['integer', 'distinct', 'exists:authors,id'],
            'category_ids' => ['sometimes', 'array'],
            'category_ids.*' => ['integer', 'distinct', 'exists:categories,id'],
        ];
    }
}
