<?php

use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CategoryController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/mangas', [CatalogController::class, 'index']);
Route::get('/mangas/{manga}', [CatalogController::class, 'show'])->whereNumber('manga');
Route::get('/categories', [CategoryController::class, 'index']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
