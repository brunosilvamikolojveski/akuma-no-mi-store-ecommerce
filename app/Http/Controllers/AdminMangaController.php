<?php

namespace App\Http\Controllers;

use App\Http\Requests\CatalogIndexRequest;
use App\Http\Requests\StoreMangaRequest;
use App\Http\Requests\UpdateMangaRequest;
use App\Http\Resources\MangaResource;
use App\Services\CatalogService;

class AdminMangaController extends Controller
{
    public function store(StoreMangaRequest $request, CatalogService $catalog)
    {
        $manga = $catalog->create($request->validated());

        return (new MangaResource($manga))
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
        $updatedManga = $catalog->update($manga, $request->validated());

        return new MangaResource($updatedManga);
    }

    public function index(CatalogIndexRequest $request, CatalogService $catalog)
    {
        // Admins also need to see inactive volumes in order to manage them.
        return MangaResource::collection(
            $catalog->list($request->validated(), includeInactive: true),
        );
    }
}
