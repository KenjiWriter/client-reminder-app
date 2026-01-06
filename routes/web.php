<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', [\App\Http\Controllers\LandingController::class, 'index'])->name('home');
Route::post('/consultation', [\App\Http\Controllers\LandingController::class, 'store'])->name('consultation.store');

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

Route::prefix('/c/{publicUid}/appointments')->name('public.client.')->group(function () {
    Route::patch('/{appointment}/request-reschedule', [\App\Http\Controllers\PublicClientController::class, 'requestReschedule'])
        ->name('request-reschedule');
    Route::patch('/{appointment}/accept-suggestion', [\App\Http\Controllers\PublicClientController::class, 'acceptSuggestion'])
        ->name('accept-suggestion');
    Route::patch('/{appointment}/reject-suggestion', [\App\Http\Controllers\PublicClientController::class, 'rejectSuggestion'])
        ->name('reject-suggestion');
    Route::delete('/{appointment}', [\App\Http\Controllers\PublicClientController::class, 'cancelAppointment'])
        ->name('cancel-appointment');
});

Route::get('dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('clients', \App\Http\Controllers\ClientController::class)->except(['edit']);
    Route::put('/clients/{client}/medical-history', [\App\Http\Controllers\MedicalHistoryController::class, 'update'])->name('clients.medical-history.update');

    Route::get('/appointments/search', [\App\Http\Controllers\AppointmentController::class, 'search'])->name('appointments.search');
    Route::get('/calendar', [\App\Http\Controllers\AppointmentController::class, 'index'])->name('calendar.index');
    Route::post('/appointments', [\App\Http\Controllers\AppointmentController::class, 'store'])->name('appointments.store');
    Route::put('/appointments/{appointment}', [\App\Http\Controllers\AppointmentController::class, 'update'])->name('appointments.update');
    Route::delete('/appointments/{appointment}', [\App\Http\Controllers\AppointmentController::class, 'destroy'])->name('appointments.destroy');


    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/account', [\App\Http\Controllers\SettingsController::class, 'editAccount'])->name('account');
        Route::get('/sms', [\App\Http\Controllers\SettingsController::class, 'editSms'])->name('sms');
        Route::put('/sms', [\App\Http\Controllers\SettingsController::class, 'updateSms'])->name('sms.update');
        Route::get('/appearance', [\App\Http\Controllers\SettingsController::class, 'editAppearance'])->name('appearance');
        
        // Medical Conditions Management
        Route::get('/medical', [\App\Http\Controllers\MedicalConditionTypeController::class, 'index'])->name('medical');
        
        // Services Management
        Route::get('/services', [\App\Http\Controllers\ServiceController::class, 'index'])->name('services');
    });

    // Medical Condition Types API routes (for Quick Add feature)
    Route::post('/medical-condition-types', [\App\Http\Controllers\MedicalConditionTypeController::class, 'store'])->name('medical-condition-types.store');
    Route::put('/medical-condition-types/{medicalConditionType}', [\App\Http\Controllers\MedicalConditionTypeController::class, 'update'])->name('medical-condition-types.update');
    Route::delete('/medical-condition-types/{medicalConditionType}', [\App\Http\Controllers\MedicalConditionTypeController::class, 'destroy'])->name('medical-condition-types.destroy');

    // Services API routes
    Route::post('/services', [\App\Http\Controllers\ServiceController::class, 'store'])->name('services.store');
    Route::put('/services/{service}', [\App\Http\Controllers\ServiceController::class, 'update'])->name('services.update');
    Route::delete('/services/{service}', [\App\Http\Controllers\ServiceController::class, 'destroy'])->name('services.destroy');

    Route::get('/messages', [\App\Http\Controllers\SmsMessageController::class, 'index'])->name('messages.index');

    Route::prefix('admin/appointments')->name('admin.appointments.')->group(function () {
        Route::get('/review', [\App\Http\Controllers\AdminAppointmentReviewController::class, 'index'])->name('review.index');
        Route::get('/availability', [\App\Http\Controllers\AdminAppointmentReviewController::class, 'availability'])->name('availability');
        Route::patch('/{appointment}/approve', [\App\Http\Controllers\AdminAppointmentReviewController::class, 'approve'])->name('approve');
        Route::patch('/{appointment}/reject-with-suggestion', [\App\Http\Controllers\AdminAppointmentReviewController::class, 'rejectWithSuggestion'])->name('reject-with-suggestion');
    });

    Route::resource('admin/leads', \App\Http\Controllers\Admin\LeadController::class)->only(['index', 'update'])->names('admin.leads');

    Route::get('/statistics', [\App\Http\Controllers\StatisticsController::class, 'index'])->name('statistics.index');
});
