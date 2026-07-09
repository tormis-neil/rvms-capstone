<?php

use App\Http\Controllers\Web\AuthController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::view('/dashboard', 'dashboard')->name('dashboard');
    // R2 Block A preview — replaced with the real controller route in Block B.
    Route::view('/vehicles', 'vehicles')->name('vehicles');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

Route::get('/', fn () => redirect()->route(auth()->check() ? 'dashboard' : 'login'));
