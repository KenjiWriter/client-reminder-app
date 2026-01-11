<?php

namespace App\Services;

use App\Contracts\SmsProvider;
use App\Models\Appointment;
use App\Models\SmsMessage;
use App\Models\Setting;
use App\ValueObjects\SmsResult;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Lang;

class AppointmentReminderSender
{
    public function __construct(
        protected SmsProvider $smsProvider
    ) {}

    public function send(Appointment $appointment, bool $force = false): SmsResult
    {
        // If force is TRUE, we skip getGuardError completely.
        if (!$force) {
            $error = $this->getGuardError($appointment);
            if ($error) {
                return SmsResult::failure($error);
            }
        }

        return $this->sendReminder($appointment, $force);
    }

    public function sendReminder(Appointment $appointment, bool $force = false): SmsResult
    {
        return $this->sendInternal($appointment, 'appointment_reminder', function($appt) {
            // Atomic check: only proceed if we can update the row where reminder_sent_at is NULL
            if (is_null($appt->reminder_sent_at)) {
                Appointment::where('id', $appt->id)->whereNull('reminder_sent_at')->update(['reminder_sent_at' => now()]);
            }
            return false;
        }, function($appt) {
            Appointment::where('id', $appt->id)->update(['reminder_sent_at' => null]);
        }, [], $force);
    }

    public function sendApproval(Appointment $appointment): SmsResult
    {
        return $this->sendInternal($appointment, 'request_approved');
    }

    public function sendSuggestion(Appointment $appointment): SmsResult
    {
        return $this->sendInternal($appointment, 'suggestion_proposed', null, null, [
            'starts_at' => $appointment->suggested_starts_at
        ]);
    }

    protected function sendInternal(
        Appointment $appointment, 
        string $template, 
        ?callable $beforeSend = null, 
        ?callable $onFailure = null,
        array $overrides = [],
        bool $force = false
    ): SmsResult {
        if (!$appointment->client || !$appointment->client->phone_e164) {
            return SmsResult::failure('Client has no phone number or client record is missing.');
        }

        if (!$force && $appointment->client->sms_opt_out) {
            return SmsResult::failure('Client has opted out of SMS reminders.');
        }

        // If beforeSend returns false explicitly, we stop.
        // This is used for atomic locking (preventing double sends).
        if ($beforeSend) {
            $shouldProceed = $beforeSend($appointment);
            if ($shouldProceed === false) {
                return SmsResult::failure('Cancelled by beforeSend check (already sent or locked).');
            }
        }

        try {
            $message = $this->composeMessage($appointment, $template, $overrides);
            $result = $this->smsProvider->send($appointment->client->phone_e164, $message);

            // Handle "No links allowed" error from SMS provider (e.g. SMSAPI)
            if (!$result->success && str_contains($result->error ?? '', 'Not allowed to send messages with link')) {
                // Log the failed attempt with link
                $this->logMessage($appointment, $message, $result);

                // Try to strip links by using _no_link template variant
                $noLinkTemplate = $template . '_no_link';
                if (Lang::has("sms.{$noLinkTemplate}", 'pl')) {
                    $message = $this->composeMessage($appointment, $noLinkTemplate, $overrides);
                    $result = $this->smsProvider->send($appointment->client->phone_e164, $message);
                }
            }

            $this->logMessage($appointment, $message, $result);

            if (!$result->success && $onFailure) {
                $onFailure($appointment);
            }

            return $result;
        } catch (\Exception $e) {
            if ($onFailure) $onFailure($appointment);
            $this->logException($appointment, isset($message) ? $message : null, $e);
            return SmsResult::failure('Internal error: ' . $e->getMessage());
        }
    }

    public function composeMessage(Appointment $appointment, string $template = 'appointment_reminder', array $overrides = []): string
    {
        $timezone = Setting::get('timezone', config('app.timezone', 'UTC'));
        $startsAt = ($overrides['starts_at'] ?? $appointment->starts_at)->timezone($timezone);

        $publicUrl = config('app.url').'/c/'.$appointment->client->public_uid;

        return trans("sms.{$template}", [
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
            // Check if it was REALLY sent by looking at logs
            $alreadySentLog = SmsMessage::where('appointment_id', $appointment->id)
                ->where('status', 'success')
                ->where('created_at', '>=', now()->subDays(1)) // Look back 1 day max
                ->exists();

            if ($alreadySentLog) {
                return 'Reminder already sent (confirmed by log).';
            }

            // If we are here, it means 'reminder_sent_at' is set, but there is no log in SmsMessage
            // This suggests a "zombie" state (locked but failed to send/log)
            // We should allow sending, effectively self-healing the state
            // But we must be careful not to spam, however the atomic lock later handles concurrency
            // So here we assume it's safe to retry if NO log exists.
            
            // We can optionally clear the flag here to "fix" the data
            // $appointment->update(['reminder_sent_at' => null]);
            
            // Or just return null (allow sending) and let the atomic update logic handle the re-locking
            // But atomic logic expects reminder_sent_at to be NULL to lock it.
            // So we MUST return a specific error OR duplicate the logic.
            // Actually, if we want to allow retry, we should probably return NULL here, 
            // BUT the atomic lock in sendReminder relies on 'reminder_sent_at' being NULL.
            // So we can returns a specific message or handle it in the caller.
            
            // However, getGuardError is called inside send() BEFORE sendReminder().
            // If we return null here, it goes to sendReminder -> sendInternal -> atomic checks.
            // The atomic callback checks "is_null($appt->reminder_sent_at)".
            // So if we don't clear it, the atomic check will fail anyway returning false.
            
            return 'Reminder marked as sent.';
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
