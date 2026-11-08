<?php

use App\Http\Controllers\AdminCategoryController;
use App\Http\Controllers\AdminMangaController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CategoryController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/mangas', [CatalogController::class, 'index']);
Route::get('/mangas/{manga}', [CatalogController::class, 'show'])->whereNumber('manga');
Route::get('/categories', [CategoryController::class, 'index']);

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::get('/mangas', [AdminMangaController::class, 'index']);
    Route::post('/mangas', [AdminMangaController::class, 'store']);
    Route::delete('/mangas/{manga}', [AdminMangaController::class, 'destroy'])->whereNumber('manga');
    Route::patch('/mangas/{manga}', [AdminMangaController::class, 'update'])->whereNumber('manga');
    Route::apiResource('categories', AdminCategoryController::class);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::get('/user', function (Request $request) {
        return new \App\Http\Resources\UserResource($request->user()->load('role'));
    });
});
