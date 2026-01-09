<?php

namespace App\Observers;

use App\Jobs\SyncToGoogleCalendar;
use App\Models\Appointment;

class AppointmentObserver
{
    public function created(Appointment $appointment): void
    {
        // Check if user (or admin) has google cal connected?
        // For MVP, we dispatch and let the service/job decide if it can sync (checks for user token)
        SyncToGoogleCalendar::dispatch($appointment->id, 'create');
    }

    public function updated(Appointment $appointment): void
    {
        // Only sync if relevant fields changed?
        // For simplicity, sync on any update or check dirty attributes
        if ($appointment->isDirty(['starts_at', 'duration_minutes', 'note', 'service_id'])) {
            SyncToGoogleCalendar::dispatch($appointment->id, 'update');
        }
    }

    public function deleted(Appointment $appointment): void
    {
        if ($appointment->google_event_id) {
            SyncToGoogleCalendar::dispatch(null, 'delete', $appointment->google_event_id);
        }
    }
}
