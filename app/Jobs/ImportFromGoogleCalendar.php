<?php

namespace App\Jobs;

use App\Models\Appointment;
use App\Models\Client;
use App\Models\User;
use App\Services\GoogleCalendarService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ImportFromGoogleCalendar implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(GoogleCalendarService $googleService)
    {
        $user = User::first();
        if (!$user) {
            Log::warning('ImportFromGoogleCalendar: No user found.');
            return;
        }

        // Fetch events from the start of today for the next 30 days
        $start = now()->startOfDay();
        $end = now()->addDays(30);

        Log::info('ImportFromGoogleCalendar: Fetching events', ['start' => $start, 'end' => $end]);

        $googleEvents = $googleService->listEvents($user, $start, $end);

        Log::info('ImportFromGoogleCalendar: Found events', ['count' => count($googleEvents)]);

        if (empty($googleEvents)) {
            return;
        }

        // Ensure "Google Calendar" client exists
        $client = Client::firstOrCreate(
            ['email' => 'google-calendar@system.local'],
            [
                'full_name' => 'Google Calendar',
                'phone_e164' => '+00000000000', // Placeholder
            ]
        );

        foreach ($googleEvents as $event) {
            $googleEventId = $event->getId();
            
            // Skip if we already have this event linked
            if (Appointment::where('google_event_id', $googleEventId)->exists()) {
                continue;
            }

            // Skip if start/end are missing (e.g. reminders/tasks might behave differently)
            if (empty($event->start->dateTime) || empty($event->end->dateTime)) {
                // Handle all-day events? For now skip
                continue;
            }

            try {
                $startTime = Carbon::parse($event->start->dateTime)->setTimezone(config('app.timezone'));
                $endTime = Carbon::parse($event->end->dateTime)->setTimezone(config('app.timezone'));
                $duration = $startTime->diffInMinutes($endTime);

                Log::info('Importing Google Event', ['summary' => $event->getSummary(), 'id' => $googleEventId]);
                
                Appointment::withoutEvents(function () use ($client, $startTime, $duration, $event, $googleEventId) {
                    Appointment::create([
                        'client_id' => $client->id,
                        'service_id' => null, 
                        'starts_at' => $startTime,
                        'duration_minutes' => $duration ?: 60,
                        'note' => $event->getSummary() . "\n" . $event->getDescription(),
                        'google_event_id' => $googleEventId,
                        'status' => Appointment::STATUS_CONFIRMED,
                    ]);
                });

            } catch (\Exception $e) {
                Log::error('Failed to import event', ['id' => $googleEventId, 'error' => $e->getMessage()]);
            }
        }
    }
}
