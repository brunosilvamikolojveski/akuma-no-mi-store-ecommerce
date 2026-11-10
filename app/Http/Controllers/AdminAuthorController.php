<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAuthorRequest;
use App\Http\Requests\UpdateAuthorRequest;
use App\Http\Resources\AuthorResource;
use App\Models\Author;

class AdminAuthorController extends Controller
{
    public function index()
    {
        return AuthorResource::collection(
            Author::query()->withCount('series')->orderBy('name')->get(),
        );
    }

    public function store(StoreAuthorRequest $request)
    {
        $author = Author::create($request->validated());

        return (new AuthorResource($author))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Author $author)
    {
        return new AuthorResource($author->loadCount('series'));
    }

    public function update(UpdateAuthorRequest $request, Author $author)
    {
        $author->update($request->validated());

        return new AuthorResource($author->refresh()->loadCount('series'));
    }

    public function destroy(Author $author)
    {
        $author->delete();

        return response()->noContent();
    }
}
