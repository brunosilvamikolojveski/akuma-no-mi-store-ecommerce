<?php

namespace App\Http\Controllers;

use App\Http\Resources\AuthorResource;
use App\Models\Author;

class AuthorController extends Controller
{
    public function index()
    {
        return AuthorResource::collection(
            Author::query()->withCount('series')->orderBy('name')->get(),
        );
    }

    public function show(int $author)
    {
        return new AuthorResource(
            Author::query()->withCount('series')->findOrFail($author),
        );
    }
}
