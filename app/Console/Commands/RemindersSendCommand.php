<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Models\Setting;
use App\Jobs\SendAppointmentReminderJob;
use App\Services\AppointmentReminderSender;
use Illuminate\Console\Command;

class RemindersSendCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reminders:send {appointment_id} {--force} {--sync}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manually send an appointment reminder SMS';

    /**
     * Execute the console command.
     */
    public function handle(AppointmentReminderSender $sender): int
    {
        $appointmentId = $this->argument('appointment_id');
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
        $this->line("- Phone:  {$appointment->client->phone_e164}");
        $this->line("- Start:  {$startsAt->format('Y-m-d H:i')} ({$timezone})");

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
}
