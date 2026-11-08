<?php

namespace App\Services;

use App\Models\Manga;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CatalogService
{
    /** Return active manga volumes matching the validated catalog filters. */
    public function list(array $filters, bool $includeInactive = false): LengthAwarePaginator
    {
        $query = Manga::query()
            ->when(! $includeInactive, fn ($query) => $query->where('is_active', true))
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
            });

        // Only the sort values allowed by the Form Request can reach this selection.
        $sort = $filters['sort'] ?? 'newest';

        if ($sort === 'price_asc') {
            $query->orderBy('price')->orderBy('id');
        } elseif ($sort === 'price_desc') {
            $query->orderByDesc('price')->orderByDesc('id');
        } else {
            $query->orderByDesc('created_at')->orderByDesc('id');
        }

        return $query
            ->paginate($filters['per_page'] ?? 12)
            ->withQueryString();
    }

    /** Find one active manga volume and load the data needed by the API resource. */
    public function findActiveById(int $id): Manga
    {
        return Manga::query()
            ->where('is_active', true)
            ->with(['series.publisher', 'series.authors:id,name', 'series.categories:id,name,slug'])
            ->findOrFail($id);
    }
}
