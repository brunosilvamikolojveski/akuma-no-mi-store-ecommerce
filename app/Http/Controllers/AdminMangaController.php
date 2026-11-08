<?php

namespace App\Http\Controllers;

use App\Http\Requests\CatalogIndexRequest;
use App\Http\Resources\MangaResource;
use App\Services\CatalogService;

class AdminMangaController extends Controller
{
    public function index(CatalogIndexRequest $request, CatalogService $catalog)
    {
        // Admins also need to see inactive volumes in order to manage them.
        return MangaResource::collection(
            $catalog->list($request->validated(), includeInactive: true),
        );
    }
}
