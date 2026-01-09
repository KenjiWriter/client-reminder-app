<?php

namespace App\Jobs;

use App\Models\Appointment;
use App\Services\GoogleCalendarService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncToGoogleCalendar implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $appointmentId;
    protected $action; // 'create', 'update', 'delete'
    protected $googleEventId; // For delete action

    /**
     * Create a new job instance.
     *
     * @param int|null $appointmentId
     * @param string $action 'create', 'update', 'delete'
     * @param string|null $googleEventId Required for 'delete'
     */
    public function __construct(?int $appointmentId, string $action, ?string $googleEventId = null)
    {
        $this->appointmentId = $appointmentId;
        $this->action = $action;
        $this->googleEventId = $googleEventId;
    }

    public function handle(GoogleCalendarService $googleService)
    {
        Log::info('SyncToGoogleCalendar: Job started', ['appointment_id' => $this->appointmentId, 'action' => $this->action]);

        if ($this->action === 'delete') {
            if ($this->googleEventId) {
                $googleService->deleteEvent($this->googleEventId);
            }
            return;
        }

        $appointment = Appointment::find($this->appointmentId);
        if (!$appointment) return;

        if ($this->action === 'create') {
            $eventId = $googleService->createEvent($appointment);
            if ($eventId) {
                $appointment->update(['google_event_id' => $eventId]);
            }
        } elseif ($this->action === 'update') {
            $googleService->updateEvent($appointment);
        }
    }
}
