<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'application' => 'Akuma no Mi Store',
        'message' => 'API ativa',
    ]);
});
