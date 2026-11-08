<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMangaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'series_id' => ['required', 'integer', 'exists:series,id'],
            'volume_number' => [
                'required',
                'integer',
                'min:1',
                'max:65535',
                Rule::unique('mangas', 'volume_number')
                    ->where(fn ($query) => $query->where('series_id', $this->input('series_id'))),
            ],
            'title' => ['nullable', 'string', 'max:180'],
            'isbn' => ['nullable', 'string', 'max:20', 'unique:mangas,isbn'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
            'stock' => ['required', 'integer', 'min:0'],
            'cover_image_path' => ['nullable', 'string', 'max:255'],
            'published_at' => ['nullable', 'date'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
