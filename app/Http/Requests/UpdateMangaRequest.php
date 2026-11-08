<?php

namespace App\Http\Requests;

use App\Models\Manga;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMangaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $mangaId = $this->route('manga');
        $seriesId = $this->input('series_id');

        if ($seriesId === null && $mangaId !== null) {
            $seriesId = Manga::whereKey($mangaId)->value('series_id');
        }

        return [
            'series_id' => ['sometimes', 'integer', 'exists:series,id'],
            'volume_number' => [
                'sometimes',
                'integer',
                'min:1',
                'max:65535',
                Rule::unique('mangas', 'volume_number')
                    ->where(fn ($query) => $query->where('series_id', $seriesId))
                    ->ignore($mangaId),
            ],
            'title' => ['sometimes', 'nullable', 'string', 'max:180'],
            'isbn' => [
                'sometimes',
                'nullable',
                'string',
                'max:20',
                Rule::unique('mangas', 'isbn')->ignore($mangaId),
            ],
            'description' => ['sometimes', 'nullable', 'string'],
            'price' => ['sometimes', 'numeric', 'min:0.01', 'max:99999999.99'],
            'stock' => ['sometimes', 'integer', 'min:0'],
            'cover_image_path' => ['sometimes', 'nullable', 'string', 'max:255'],
            'published_at' => ['sometimes', 'nullable', 'date'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
