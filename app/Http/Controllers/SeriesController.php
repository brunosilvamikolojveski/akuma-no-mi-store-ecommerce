<?php

namespace App\Http\Controllers;

use App\Http\Resources\SeriesResource;
use App\Services\SeriesService;

class SeriesController extends Controller
{
    public function index(SeriesService $series)
    {
        return SeriesResource::collection($series->list());
    }

    public function show(string $series, SeriesService $catalog)
    {
        return new SeriesResource($catalog->findBySlug($series));
    }
}
