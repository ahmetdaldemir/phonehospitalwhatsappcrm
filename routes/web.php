<?php

use App\Http\Controllers\LandingPageController;
use Illuminate\Support\Facades\Route;

// Landing page
Route::get('/', [LandingPageController::class, 'index'])->name('landing');

// SPA route - all other routes handled by Vue Router
Route::get('/{any}', function () {
    return view('app');
})->where('any', '^(?!api).*');

