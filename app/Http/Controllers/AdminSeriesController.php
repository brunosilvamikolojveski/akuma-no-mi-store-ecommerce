<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSeriesRequest;
use App\Http\Requests\UpdateSeriesRequest;
use App\Http\Resources\SeriesResource;
use App\Models\Series;
use App\Services\SeriesService;

class AdminSeriesController extends Controller
{
    public function index(SeriesService $seriesService)
    {
        return SeriesResource::collection($seriesService->list());
    }

    public function store(StoreSeriesRequest $request, SeriesService $seriesService)
    {
        $created = $seriesService->create($request->validated());

        return (new SeriesResource($created))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Series $series, SeriesService $seriesService)
    {
        return new SeriesResource($seriesService->findById($series->id));
    }

    public function update(UpdateSeriesRequest $request, Series $series, SeriesService $seriesService)
    {
        return new SeriesResource($seriesService->update($series->id, $request->validated()));
    }

    public function destroy(Series $series, SeriesService $seriesService)
    {
        $seriesService->delete($series->id);

        return response()->noContent();
    }
}
