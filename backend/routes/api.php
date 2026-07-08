<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| RVMS API Routes — all endpoints live under /api/v1 (Sanctum bearer auth)
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    require __DIR__.'/api_v1.php';
});
