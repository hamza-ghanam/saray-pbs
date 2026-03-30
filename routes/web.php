<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'status'  => true,
        'message' => 'API is running.',
    ]);
});
