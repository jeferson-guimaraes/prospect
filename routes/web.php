<?php

use App\Http\Controllers\ProspeccaoController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');

    Route::resource('prospects', ProspeccaoController::class)->except('show');
});

require __DIR__.'/settings.php';
