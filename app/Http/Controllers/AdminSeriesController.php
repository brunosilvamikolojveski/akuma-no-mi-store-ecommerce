<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSeriesRequest;
use App\Http\Requests\UpdateSeriesRequest;
use App\Http\Resources\SeriesResource;
use App\Models\Series;
use App\Services\SeriesService;

class AdminSeriesController extends Controller
{
    public function index(SeriesService $series)
    {
        return SeriesResource::collection($series->list());
    }

    public function store(StoreSeriesRequest $request, SeriesService $series)
    {
        $created = $series->create($request->validated());

        return (new SeriesResource($created))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Series $series, SeriesService $catalog)
    {
        return new SeriesResource($catalog->findById($series->id));
    }

    public function update(UpdateSeriesRequest $request, Series $series, SeriesService $catalog)
    {
        return new SeriesResource($catalog->update($series->id, $request->validated()));
    }

    public function destroy(Series $series, SeriesService $catalog)
    {
        $catalog->delete($series->id);

        return response()->noContent();
    }
}
