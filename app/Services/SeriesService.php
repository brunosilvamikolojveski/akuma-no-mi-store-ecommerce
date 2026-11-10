<?php

namespace App\Services;

use App\Models\Series;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class SeriesService
{
    public function list()
    {
        return Series::query()
            ->with(['publisher:id,name', 'authors:id,name', 'categories:id,name,slug'])
            ->withCount('mangas')
            ->orderBy('title')
            ->get();
    }

    public function findBySlug(string $slug): Series
    {
        return Series::query()
            ->with(['publisher:id,name', 'authors:id,name', 'categories:id,name,slug'])
            ->withCount('mangas')
            ->where('slug', $slug)
            ->firstOrFail();
    }

    public function findById(int $id): Series
    {
        return Series::query()
            ->with(['publisher:id,name', 'authors:id,name', 'categories:id,name,slug'])
            ->withCount('mangas')
            ->findOrFail($id);
    }

    public function create(array $attributes): Series
    {
        return DB::transaction(function () use ($attributes) {
            $series = Series::create(Arr::only($attributes, [
                'publisher_id', 'title', 'slug', 'description', 'status',
            ]));

            $series->authors()->sync($attributes['author_ids'] ?? []);
            $series->categories()->sync($attributes['category_ids'] ?? []);

            return $this->findById($series->id);
        });
    }

    public function update(int $id, array $attributes): Series
    {
        return DB::transaction(function () use ($id, $attributes) {
            $series = Series::findOrFail($id);
            $series->update(Arr::only($attributes, [
                'publisher_id', 'title', 'slug', 'description', 'status',
            ]));

            if (array_key_exists('author_ids', $attributes)) {
                $series->authors()->sync($attributes['author_ids']);
            }

            if (array_key_exists('category_ids', $attributes)) {
                $series->categories()->sync($attributes['category_ids']);
            }

            return $this->findById($series->id);
        });
    }

    public function delete(int $id): void
    {
        $series = Series::findOrFail($id);

        if ($series->mangas()->exists()) {
            abort(409, 'Não é possível excluir uma série que possui volumes cadastrados.');
        }

        $series->delete();
    }
}
