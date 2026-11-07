<?php

namespace App\Http\Controllers;

use App\Http\Requests\CatalogIndexRequest;
use App\Models\Manga;
use Illuminate\Support\Facades\Storage;

class CatalogController extends Controller
{
    public function index(CatalogIndexRequest $request)
    {
        $filters = $request->validated();

        $mangas = Manga::query()
            ->where('is_active', true)
            ->with(['series.publisher', 'series.authors:id,name', 'series.categories:id,name,slug'])
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('series', function ($seriesQuery) use ($search) {
                            $seriesQuery->where('title', 'like', "%{$search}%");
                        });
                });
            })
            ->when($filters['category'] ?? null, function ($query, $category) {
                $query->whereHas('series.categories', function ($categoryQuery) use ($category) {
                    $categoryQuery->where('slug', $category);
                });
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($filters['per_page'] ?? 12)
            ->withQueryString();

        return response()->json([
            'data' => $mangas->getCollection()
                ->map(fn (Manga $manga) => $this->catalogItem($manga))
                ->values(),
            'links' => [
                'first' => $mangas->url(1),
                'last' => $mangas->url($mangas->lastPage()),
                'prev' => $mangas->previousPageUrl(),
                'next' => $mangas->nextPageUrl(),
            ],
            'meta' => [
                'current_page' => $mangas->currentPage(),
                'last_page' => $mangas->lastPage(),
                'per_page' => $mangas->perPage(),
                'total' => $mangas->total(),
            ],
        ]);
    }

    public function show(string $manga)
    {
        $manga = Manga::query()
            ->where('is_active', true)
            ->with(['series.publisher', 'series.authors:id,name', 'series.categories:id,name,slug'])
            ->findOrFail($manga);

        return response()->json(['data' => $this->catalogItem($manga)]);
    }

    private function catalogItem(Manga $manga): array
    {
        return [
            'id' => $manga->id,
            'title' => $manga->title ?: $manga->series->title,
            'volume_number' => $manga->volume_number,
            'isbn' => $manga->isbn,
            'description' => $manga->description,
            'price' => $manga->price,
            'in_stock' => $manga->stock > 0,
            'cover_image_url' => $manga->cover_image_path
                ? Storage::disk('public')->url($manga->cover_image_path)
                : null,
            'published_at' => $manga->published_at?->toDateString(),
            'series' => [
                'id' => $manga->series->id,
                'title' => $manga->series->title,
                'slug' => $manga->series->slug,
                'status' => $manga->series->status,
                'publisher' => $manga->series->publisher?->name,
                'authors' => $manga->series->authors->pluck('name')->values(),
                'categories' => $manga->series->categories->map(fn ($category) => [
                    'name' => $category->name,
                    'slug' => $category->slug,
                ])->values(),
            ],
        ];
    }
}
