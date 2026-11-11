<?php

namespace App\Http\Controllers;

use App\Http\Resources\SeriesResource;
use App\Services\SeriesService;

class SeriesController extends Controller
{
    public function index(SeriesService $seriesService)
    {
        return SeriesResource::collection($seriesService->list());
    }

    public function show(string $series, SeriesService $seriesService)
    {
        return new SeriesResource($seriesService->findBySlug($series));
    }
}
