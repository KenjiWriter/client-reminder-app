<?php

namespace App\Jobs;

use App\Contracts\SmsProvider;
use App\Models\Appointment;
use App\Models\SmsMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendAppointmentReminderJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $appointmentId,
        public bool $isForced = false,
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(\App\Services\AppointmentReminderSender $sender): void
    {
        $appointment = Appointment::with('client')->find($this->appointmentId);

        if (! $appointment) {
            Log::warning('Appointment not found for reminder', ['appointment_id' => $this->appointmentId]);

            return;
        }

        $result = $sender->send($appointment, $this->isForced);

        if (!$result->success) {
            Log::error('Job failed to send reminder', [
                'appointment_id' => $this->appointmentId,
                'error' => $result->error,
            ]);

            throw new \RuntimeException("SMS send failed: {$result->error}");
        }
    }


}
