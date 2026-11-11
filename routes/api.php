<?php

use App\Http\Controllers\AdminAuthorController;
use App\Http\Controllers\AdminCategoryController;
use App\Http\Controllers\AdminMangaController;
use App\Http\Controllers\AdminSeriesController;
use App\Http\Controllers\AddressController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AuthorController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\SeriesController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/mangas', [CatalogController::class, 'index']);
Route::get('/mangas/{manga}', [CatalogController::class, 'show'])->whereNumber('manga');
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/authors', [AuthorController::class, 'index']);
Route::get('/authors/{author}', [AuthorController::class, 'show'])->whereNumber('author');
Route::get('/series', [SeriesController::class, 'index']);
Route::get('/series/{series:slug}', [SeriesController::class, 'show']);

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::get('/mangas', [AdminMangaController::class, 'index']);
    Route::post('/mangas', [AdminMangaController::class, 'store']);
    Route::delete('/mangas/{manga}', [AdminMangaController::class, 'destroy'])->whereNumber('manga');
    Route::patch('/mangas/{manga}', [AdminMangaController::class, 'update'])->whereNumber('manga');
    Route::apiResource('categories', AdminCategoryController::class);
    Route::apiResource('authors', AdminAuthorController::class);
    Route::apiResource('series', AdminSeriesController::class);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::apiResource('addresses', AddressController::class);
    Route::get('/cart', [CartController::class, 'show']);
    Route::post('/cart/items', [CartController::class, 'addItem']);
    Route::patch('/cart/items/{cartItem}', [CartController::class, 'updateItem'])->whereNumber('cartItem');
    Route::delete('/cart/items/{cartItem}', [CartController::class, 'removeItem'])->whereNumber('cartItem');
    Route::delete('/cart', [CartController::class, 'clear']);
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::post('/orders/{order}/confirm-payment', [OrderController::class, 'confirmPayment'])->whereNumber('order');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->whereNumber('order');

    Route::get('/user', function (Request $request) {
        return new \App\Http\Resources\UserResource($request->user()->load('role'));
    });
});
