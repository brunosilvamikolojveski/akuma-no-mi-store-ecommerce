<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SeriesResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'status' => $this->status,
            'publisher' => $this->publisher?->name,
            'authors' => $this->authors->map(fn ($author) => [
                'id' => $author->id,
                'name' => $author->name,
            ])->values(),
            'categories' => $this->categories->map(fn ($category) => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
            ])->values(),
            'mangas_count' => $this->whenCounted('mangas'),
        ];
    }
}
