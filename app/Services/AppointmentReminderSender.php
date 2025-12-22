<?php

namespace App\Services;

use App\Contracts\SmsProvider;
use App\Models\Appointment;
use App\Models\SmsMessage;
use App\Models\Setting;
use App\ValueObjects\SmsResult;
use Illuminate\Support\Facades\Log;

class AppointmentReminderSender
{
    public function __construct(
        protected SmsProvider $smsProvider
    ) {}

    /**
     * Send a reminder for the given appointment.
     *
     * @param Appointment $appointment
     * @param bool $force Bypass standard guards (already sent, opt-out, past date)
     * @return SmsResult
     */
    public function send(Appointment $appointment, bool $force = false): SmsResult
    {
        if (!$force) {
            $error = $this->getGuardError($appointment);
            if ($error) {
                return SmsResult::failure($error);
            }
        }

        // Validate client and phone number as a baseline even for forced sends
        if (!$appointment->client || !$appointment->client->phone_e164) {
            return SmsResult::failure('Client has no phone number or client record is missing.');
        }

        // Idempotency check: Only mark as sent if not already sent or if forced
        // For forced sends, we don't overwrite reminder_sent_at if it exists
        $shouldMarkSent = is_null($appointment->reminder_sent_at);

        if ($shouldMarkSent) {
            Appointment::where('id', $appointment->id)
                ->whereNull('reminder_sent_at')
                ->update(['reminder_sent_at' => now()]);
        }

        try {
            $message = $this->composeMessage($appointment);
            $result = $this->smsProvider->send($appointment->client->phone_e164, $message);

            $this->logMessage($appointment, $message, $result);

            if (!$result->success && $shouldMarkSent) {
                // Rollback if we were the ones marking it as sent
                Appointment::where('id', $appointment->id)->update(['reminder_sent_at' => null]);
            }

            return $result;
        } catch (\Exception $e) {
            if ($shouldMarkSent) {
                Appointment::where('id', $appointment->id)->update(['reminder_sent_at' => null]);
            }

            $this->logException($appointment, isset($message) ? $message : null, $e);
            
            return SmsResult::failure('Internal error: ' . $e->getMessage());
        }
    }

    public function composeMessage(Appointment $appointment): string
    {
        $timezone = Setting::get('timezone', config('app.timezone', 'UTC'));
        $startsAt = $appointment->starts_at->timezone($timezone);

        $publicUrl = config('app.url').'/c/'.$appointment->client->public_uid;

        return trans('sms.appointment_reminder', [
            'date' => $startsAt->locale('pl')->translatedFormat('j F Y'),
            'time' => $startsAt->format('H:i'),
            'link' => $publicUrl,
        ], 'pl');
    }

    protected function getGuardError(Appointment $appointment): ?string
    {
        if (!$appointment->send_reminder) {
            return 'Reminder is disabled for this appointment.';
        }

        if ($appointment->reminder_sent_at) {
            return 'Reminder already sent.';
        }

        if ($appointment->client && $appointment->client->sms_opt_out) {
            return 'Client has opted out of SMS reminders.';
        }

        if ($appointment->starts_at->isPast()) {
            return 'Appointment is in the past.';
        }

        return null;
    }

    protected function logMessage(Appointment $appointment, string $message, SmsResult $result): void
    {
        SmsMessage::create([
            'provider' => config('sms.driver', 'log'),
            'to_e164' => $appointment->client->phone_e164,
            'message_hash' => hash('sha256', $message),
            'status' => $result->success ? 'success' : 'failed',
            'error' => $result->error,
            'appointment_id' => $appointment->id,
            'client_id' => $appointment->client_id,
            'provider_message_id' => $result->providerMessageId,
            'sent_at' => now(),
        ]);
    }

    protected function logException(Appointment $appointment, ?string $message, \Exception $e): void
    {
        SmsMessage::create([
            'provider' => config('sms.driver', 'log'),
            'to_e164' => $appointment->client->phone_e164 ?? 'unknown',
            'message_hash' => $message ? hash('sha256', $message) : 'unknown',
            'status' => 'failed',
            'error' => "Exception: " . $e->getMessage(),
            'appointment_id' => $appointment->id,
            'client_id' => $appointment->client_id,
            'sent_at' => now(),
        ]);
    }
}
