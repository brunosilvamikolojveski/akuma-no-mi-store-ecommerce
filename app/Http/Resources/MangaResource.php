<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class MangaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title ?: $this->series->title,
            'volume_number' => $this->volume_number,
            'isbn' => $this->isbn,
            'description' => $this->description,
            'price' => $this->price,
            'in_stock' => $this->stock > 0,
            'cover_image_url' => $this->cover_image_path
                ? Storage::disk('public')->url($this->cover_image_path)
                : null,
            'published_at' => $this->published_at?->toDateString(),
            'series' => [
                'id' => $this->series->id,
                'title' => $this->series->title,
                'slug' => $this->series->slug,
                'status' => $this->series->status,
                'publisher' => $this->series->publisher?->name,
                'authors' => $this->series->authors->pluck('name')->values(),
                'categories' => $this->series->categories->map(fn ($category) => [
                    'name' => $category->name,
                    'slug' => $category->slug,
                ])->values(),
            ],
        ];
    }
}
