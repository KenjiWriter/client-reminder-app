<?php

namespace App\Jobs;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncAllFutureAppointments implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        //
    }

    public function handle(): void
    {
        // Sync all future active appointments (status confirmed/completed/pending?) 
        // We probably want to sync all valid appointments.
        // Assuming we sync everything from "today" onwards.
        
        $appointments = Appointment::where('starts_at', '>=', now()->startOfDay())
            ->whereIn('status', ['confirmed', 'completed', 'pending_approval']) // Don't sync cancelled?
            ->get();

        Log::info('SyncAllFutureAppointments: Found appointments to sync', ['count' => $appointments->count()]);

        foreach ($appointments as $appointment) {
            Log::info('Dispatching sync for appointment', ['id' => $appointment->id]);
            // If already has event ID, update, else create
            $action = $appointment->google_event_id ? 'update' : 'create';
            SyncToGoogleCalendar::dispatch($appointment->id, $action); 
        }
    }
}
