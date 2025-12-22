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
        $reminderHours = (int) Setting::get('reminder_hours', 24);
        $now = Carbon::now();
        $windowEnd = $now->copy()->addMinutes(10); // Check 10-minute window

        // Adjust target time based on business setting
        // We look for appointments starting in (reminderHours) from now.
        $targetStart = $now->copy()->addHours($reminderHours);
        $targetEnd = $windowEnd->copy()->addHours($reminderHours);

        $appointments = Appointment::where('send_reminder', true)
            ->where('status', Appointment::STATUS_CONFIRMED)
            ->whereNull('reminder_sent_at')
            ->whereBetween('starts_at', [$targetStart, $targetEnd])
            ->get();

        if ($appointments->isEmpty()) {
            $this->info("No reminders to send for the window: {$targetStart->format('H:i')} - {$targetEnd->format('H:i')}");
            return 0;
        }

        $this->info("Found {$appointments->count()} appointments needing reminders.");

        foreach ($appointments as $appointment) {
            $this->line("- Dispatching reminder for Appointment #{$appointment->id} (Client ID: {$appointment->client_id})");
            SendAppointmentReminderJob::dispatch($appointment->id);
        }

        $this->info("All reminder jobs dispatched.");
        return 0;
    }
}
