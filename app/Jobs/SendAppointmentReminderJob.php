<?php

namespace App\Jobs;

use App\Contracts\SmsProvider;
use App\Models\Appointment;
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
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(SmsProvider $smsProvider): void
    {
        $appointment = Appointment::with('client')->find($this->appointmentId);

        if (! $appointment) {
            Log::warning('Appointment not found for reminder', ['appointment_id' => $this->appointmentId]);

            return;
        }

        // Idempotency check: Only send if reminder_sent_at is null
        // Use atomic update to prevent race conditions
        $updated = Appointment::where('id', $appointment->id)
            ->whereNull('reminder_sent_at')
            ->update(['reminder_sent_at' => now()]);

        if ($updated === 0) {
            Log::info('Reminder already sent for appointment', ['appointment_id' => $this->appointmentId]);

            return;
        }

        try {
            $message = $this->composeMessage($appointment);
            $result = $smsProvider->send($appointment->client->phone_e164, $message);

            if (! $result->success) {
                // Rollback the reminder_sent_at if send failed
                $appointment->update(['reminder_sent_at' => null]);

                Log::error('Failed to send appointment reminder', [
                    'appointment_id' => $appointment->id,
                    'error' => $result->error,
                ]);

                throw new \RuntimeException("SMS send failed: {$result->error}");
            }

            Log::info('Appointment reminder sent successfully', [
                'appointment_id' => $appointment->id,
                'client_id' => $appointment->client_id,
                'message_id' => $result->providerMessageId,
            ]);
        } catch (\Exception $e) {
            // Rollback on any exception
            $appointment->update(['reminder_sent_at' => null]);

            throw $e;
        }
    }

    private function composeMessage(Appointment $appointment): string
    {
        $timezone = config('app.timezone', 'UTC');
        $startsAt = $appointment->starts_at->timezone($timezone);

        $publicUrl = config('app.url').'/c/'.$appointment->client->public_uid;
        $footerNote = config('sms.footer_note', '');

        $message = "Hi {$appointment->client->full_name}!\n\n";
        $message .= "Reminder: You have an appointment on {$startsAt->format('l, F j')} at {$startsAt->format('g:i A')}.\n\n";
        $message .= "View your appointments: {$publicUrl}\n";

        if ($footerNote) {
            $message .= "\n{$footerNote}\n";
        }

        $message .= "\nTo stop reminders, visit the link above.";

        return $message;
    }
}
