<?php

use Illuminate\Support\Facades\Route;

// R1 Block A preview routes — replaced with real auth routes in Block B.
Route::get('/login', fn () => view('auth.login'))->name('login');
Route::get('/dashboard', fn () => view('dashboard'))->name('dashboard');

Route::get('/', fn () => redirect()->route('login'));
