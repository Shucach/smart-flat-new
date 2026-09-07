<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/**
 * The landing page says nothing about what runs here, so anybody already signed
 * in has no reason to look at it and goes straight to their dashboard.
 */
Route::get('/', fn (Request $request) => $request->user()
    ? redirect()->route('dashboard')
    : Inertia::render('Welcome'))->name('home');

Route::middleware('auth')->group(function (): void {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
});

require __DIR__.'/smartflat.php';
require __DIR__.'/settings.php';
