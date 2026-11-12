<?php

namespace App\Http\Controllers;

use App\Http\Requests\CatalogIndexRequest;
use App\Http\Requests\StoreMangaRequest;
use App\Http\Requests\UpdateMangaRequest;
use App\Http\Resources\AdminMangaResource;
use App\Models\Manga;
use App\Services\CatalogService;
use Illuminate\Support\Facades\Storage;

class AdminMangaController extends Controller
{
    public function store(StoreMangaRequest $request, CatalogService $catalog)
    {
        $attributes = $request->validated();

        if ($request->hasFile('cover_image')) {
            $attributes['cover_image_path'] = $request->file('cover_image')->store('manga-covers', 'public');
        }

        unset($attributes['cover_image']);
        $manga = $catalog->create($attributes);

        return (new AdminMangaResource($manga))
            ->response()
            ->setStatusCode(201);
    }

    public function destroy(int $manga, CatalogService $catalog)
    {
        $catalog->deactivate($manga);

        return response()->noContent();
    }

    public function update(UpdateMangaRequest $request, int $manga, CatalogService $catalog)
    {
        $attributes = $request->validated();
        $oldCoverPath = Manga::whereKey($manga)->value('cover_image_path');
        $hasNewCover = $request->hasFile('cover_image');

        if ($hasNewCover) {
            $attributes['cover_image_path'] = $request->file('cover_image')->store('manga-covers', 'public');
        }

        unset($attributes['cover_image']);
        $updatedManga = $catalog->update($manga, $attributes);

        if ($hasNewCover && $oldCoverPath && ! preg_match('/^https?:\/\//i', $oldCoverPath)) {
            Storage::disk('public')->delete($oldCoverPath);
        }

        return new AdminMangaResource($updatedManga);
    }

    public function index(CatalogIndexRequest $request, CatalogService $catalog)
    {
        // Admins also need to see inactive volumes in order to manage them.
        return AdminMangaResource::collection(
            $catalog->list($request->validated(), includeInactive: true),
        );
    }
}
