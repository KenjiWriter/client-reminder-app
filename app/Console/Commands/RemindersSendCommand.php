<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Models\Setting;
use App\Jobs\SendAppointmentReminderJob;
use App\Services\AppointmentReminderSender;
use Illuminate\Console\Command;
use Carbon\Carbon;

class RemindersSendCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reminders:send {appointment_id?} {--force} {--sync}';

    /**
     * Execute the console command.
     */
    public function handle(AppointmentReminderSender $sender): int
    {
        $appointmentId = $this->argument('appointment_id');

        if ($appointmentId) {
            return $this->handleIndividual($appointmentId, $sender);
        }

        return $this->handleBulk($sender);
    }

    protected function handleIndividual(string $appointmentId, AppointmentReminderSender $sender): int
    {
        $force = $this->option('force');
        $sync = $this->option('sync');

        $appointment = Appointment::with('client')->find($appointmentId);

        if (!$appointment) {
            $this->error("Appointment #{$appointmentId} not found.");
            return 1;
        }

        $timezone = Setting::get('timezone', config('app.timezone', 'UTC'));
        $startsAt = $appointment->starts_at->timezone($timezone);

        $this->info("Appointment Summary:");
        $this->line("- Client: {$appointment->client->full_name}");
        $this->line("- Status: {$appointment->status}");
        $this->line("- Start:  {$startsAt->format('Y-m-d H:i')} ({$timezone})");

        if ($appointment->status !== Appointment::STATUS_CONFIRMED && !$force) {
            $this->warn("Warning: Appointment is not confirmed (status: {$appointment->status}). Reminder might be skipped unless --force is used.");
        }

        if ($sync) {
            $this->comment("Sending immediately...");
            $result = $sender->send($appointment, $force);

            if ($result->success) {
                $this->info("Success! Provider ID: " . ($result->providerMessageId ?? 'N/A'));
                return 0;
            } else {
                $this->error("Failed: " . $result->error);
                return 1;
            }
        }

        $this->comment("Dispatching to queue...");
        SendAppointmentReminderJob::dispatch($appointment->id, $force);
        $this->info("Job dispatched successfully.");

        return 0;
    }

    protected function handleBulk(AppointmentReminderSender $sender): int
    {
        $settings = Setting::first();
        $sendTime = $settings->sms_send_time ?? '09:00';
        
        $now = now();
        // Use parse instead of createFromFormat to be more flexible with seconds (e.g. 09:00:00)
        $scheduledTime = Carbon::parse($sendTime);
        $windowEnd = $scheduledTime->copy()->addMinutes(60);

        // Time Check: Run if current time is within 60 minutes after the scheduled time
        // This handles cases where the worker might be slightly delayed or the exact minute is missed
        if (! $this->option('force')) {
            if ($now->lt($scheduledTime) || $now->gte($windowEnd)) {
                return 0;
            }
        }

        $this->info("Time match ({$scheduledTime->format('H:i')} - {$windowEnd->format('H:i')})! Starting bulk reminder process...");

        // Select all confirmed appointments for TOMORROW
        // We don't use 'reminder_hours' anymore for this daily batch logic
        $tomorrow = Carbon::tomorrow();
        
        $appointments = Appointment::where('send_reminder', true)
            ->where('status', Appointment::STATUS_CONFIRMED)
            ->whereNull('reminder_sent_at')
            ->whereDate('starts_at', $tomorrow)
            ->get();

        if ($appointments->isEmpty()) {
            $this->info("No appointments found for tomorrow ({$tomorrow->format('Y-m-d')}).");
            return 0;
        }

        $this->info("Found {$appointments->count()} appointments for tomorrow.");

        foreach ($appointments as $appointment) {
            $this->line("- Dispatching reminder for Appointment #{$appointment->id} (Client: {$appointment->client->full_name})");
            SendAppointmentReminderJob::dispatch($appointment->id);
        }

        $this->info("All reminder jobs dispatched.");
        return 0;
    }
}
