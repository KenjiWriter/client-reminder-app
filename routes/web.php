<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('clients', \App\Http\Controllers\ClientController::class);

    Route::get('/calendar', function () {
        return Inertia::render('Calendar/Index');
    })->name('calendar.index');

    Route::get('/settings', function () {
        return Inertia::render('Settings/Index');
    })->name('settings.index');
});

require __DIR__.'/settings.php';
