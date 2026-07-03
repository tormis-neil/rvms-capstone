<?php

use Illuminate\Support\Facades\Route;

// Public (no token)

// Authenticated (Sanctum bearer token)
Route::middleware('auth:sanctum')->group(function () {
    //
});
