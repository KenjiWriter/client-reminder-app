<?php

namespace App\Console\Commands;

use App\Jobs\SendAppointmentReminderJob;
use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendAppointmentRemindersCommand extends Command
{
    protected $signature = 'reminders:send';

    protected $description = 'Send appointment reminders for appointments due in the next 5 minutes';

    public function handle(): int
    {
        $reminderHours = (int) \App\Models\Setting::get('reminder_hours', 24);

        // Find appointments where reminder should be sent in the next 5-minute window
        $now = Carbon::now();
        $windowEnd = $now->copy()->addMinutes(5);

        $appointments = Appointment::with('client')
            ->where('send_reminder', true)
            ->whereNull('reminder_sent_at')
            ->whereHas('client', function ($query) {
                $query->where('sms_opt_out', false);
            })
            ->where('starts_at', '>', $now) // future appointments only
            ->whereBetween('starts_at', [
                $now->copy()->addHours($reminderHours),
                $windowEnd->copy()->addHours($reminderHours),
            ])
            ->get();

        $count = $appointments->count();

        if ($count === 0) {
            $this->info('No reminders to send.');

            return self::SUCCESS;
        }

        $this->info("Dispatching {$count} reminder(s)...");

        foreach ($appointments as $appointment) {
            SendAppointmentReminderJob::dispatch($appointment->id);

            $this->line("  - Appointment #{$appointment->id} for {$appointment->client->full_name}");
        }

        $this->info('Reminders dispatched successfully.');

        return self::SUCCESS;
    }
}
