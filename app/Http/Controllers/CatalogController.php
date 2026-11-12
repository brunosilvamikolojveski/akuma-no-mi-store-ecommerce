<?php

namespace App\Http\Controllers;

use App\Http\Requests\CatalogIndexRequest;
use App\Http\Resources\MangaDetailResource;
use App\Http\Resources\MangaResource;
use App\Services\CatalogService;

class CatalogController extends Controller
{
    public function index(CatalogIndexRequest $request, CatalogService $catalog)
    {
        $mangas = $catalog->list($request->validated());

        return MangaResource::collection($mangas);
    }

    public function show(int $manga, CatalogService $catalog)
    {
        return new MangaDetailResource($catalog->findActiveById($manga));
    }
}
