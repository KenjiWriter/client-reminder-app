<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

// Locale switcher
Route::post('/locale', function(\Illuminate\Http\Request $request) {
    $locale = $request->input('locale');
    if (in_array($locale, ['pl', 'en'])) {
        $request->session()->put('locale', $locale);
    }
    return redirect()->back();
})->name('locale.switch');

// Public client page (no auth required)
Route::get('/c/{publicUid}', [\App\Http\Controllers\PublicClientController::class, 'show'])
    ->name('public.client.show');
Route::post('/c/{publicUid}/opt-out', [\App\Http\Controllers\PublicClientController::class, 'toggleOptOut'])
    ->name('public.client.toggle-opt-out');
Route::get('/c/{publicUid}/availability', [\App\Http\Controllers\PublicClientController::class, 'availability'])
    ->name('public.client.availability');
Route::patch('/c/{publicUid}/appointments/{appointment}/request-reschedule', [\App\Http\Controllers\PublicClientController::class, 'requestReschedule'])
    ->name('public.client.request-reschedule');
Route::patch('/c/{publicUid}/appointments/{appointment}/accept-suggestion', [\App\Http\Controllers\PublicClientController::class, 'acceptSuggestion'])
    ->name('public.client.accept-suggestion');
Route::patch('/c/{publicUid}/appointments/{appointment}/reject-suggestion', [\App\Http\Controllers\PublicClientController::class, 'rejectSuggestion'])
    ->name('public.client.reject-suggestion');

Route::get('dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('clients', \App\Http\Controllers\ClientController::class);

    Route::get('/calendar', [\App\Http\Controllers\AppointmentController::class, 'index'])->name('calendar.index');
    Route::post('/appointments', [\App\Http\Controllers\AppointmentController::class, 'store'])->name('appointments.store');
    Route::put('/appointments/{appointment}', [\App\Http\Controllers\AppointmentController::class, 'update'])->name('appointments.update');
    Route::delete('/appointments/{appointment}', [\App\Http\Controllers\AppointmentController::class, 'destroy'])->name('appointments.destroy');


    Route::get('/settings', [\App\Http\Controllers\SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings', [\App\Http\Controllers\SettingsController::class, 'update'])->name('settings.update');

    Route::get('/messages', [\App\Http\Controllers\SmsMessageController::class, 'index'])->name('messages.index');

    Route::prefix('admin/appointments')->name('admin.appointments.')->group(function () {
        Route::get('/review', [\App\Http\Controllers\AdminAppointmentReviewController::class, 'index'])->name('review.index');
        Route::patch('/{appointment}/approve', [\App\Http\Controllers\AdminAppointmentReviewController::class, 'approve'])->name('approve');
        Route::patch('/{appointment}/reject-with-suggestion', [\App\Http\Controllers\AdminAppointmentReviewController::class, 'rejectWithSuggestion'])->name('reject-with-suggestion');
    });
});

require __DIR__.'/settings.php';
