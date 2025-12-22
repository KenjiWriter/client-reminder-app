<?php

use App\Models\Appointment;
use App\Models\Client;
use App\Models\SmsMessage;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use App\Jobs\SendAppointmentReminderJob;
use Carbon\Carbon;

uses(RefreshDatabase::class);

afterEach(function () {
    Carbon::setTestNow();
});

test('it handles missing appointment', function () {
    $this->artisan('reminders:send 999')
         ->assertExitCode(1);
});

test('command refuses to send if already sent and not forced', function () {
    $appointment = Appointment::factory()->create([
        'reminder_sent_at' => now(),
        'starts_at' => now()->addDay(),
        'send_reminder' => true,
    ]);

    $this->artisan("reminders:send {$appointment->id} --sync")
        ->assertExitCode(1)
        ->expectsOutputToContain('already sent');
});

test('command sends reminder if forced even if already sent', function () {
    Carbon::setTestNow($now = Carbon::parse('2025-01-01 12:00:00'));

    $mock = Mockery::mock(\App\Contracts\SmsProvider::class);
    $mock->shouldReceive('send')->andReturn(\App\ValueObjects\SmsResult::success('mock-id'));
    app()->instance(\App\Contracts\SmsProvider::class, $mock);

    $appointment = Appointment::factory()->create([
        'reminder_sent_at' => $now->copy()->subDay(),
        'starts_at' => $now->copy()->addDay(),
        'send_reminder' => true,
        'status' => Appointment::STATUS_CONFIRMED,
    ]);

    $initialSentAt = $appointment->reminder_sent_at;

    \Illuminate\Support\Facades\Artisan::call('reminders:send', [
        'appointment_id' => $appointment->id,
        '--force' => true,
        '--sync' => true,
    ]);

    $appointment->refresh();
    expect($appointment->reminder_sent_at->timestamp)->toEqual($initialSentAt->timestamp);
    expect(SmsMessage::where('appointment_id', $appointment->id)->count())->toBe(1);
});

test('command respects client opt-out without force', function () {
    $client = Client::factory()->create(['sms_opt_out' => true]);
    $appointment = Appointment::factory()->create([
        'client_id' => $client->id,
        'starts_at' => now()->addDay(),
        'send_reminder' => true,
        'reminder_sent_at' => null,
    ]);

    $this->artisan("reminders:send {$appointment->id} --sync")
        ->assertExitCode(1)
        ->expectsOutputToContain('opted out');
});

test('bulk command dispatches reminders', function () {
    Queue::fake();
    
    Carbon::setTestNow($now = Carbon::parse('2025-01-01 12:00:00'));
    Setting::set('reminder_hours', 24);
    
    // Create appointment in window (24h 2m away)
    $appointment = Appointment::factory()->create([
        'starts_at' => $now->copy()->addHours(24)->addMinutes(2)->startOfMinute(),
        'send_reminder' => true,
        'reminder_sent_at' => null,
    ]);
    
    $appointment->refresh();

    $appointment->refresh();

    $this->artisan('reminders:send')
        ->expectsOutputToContain('Found 1 appointments needing reminders.')
        ->assertExitCode(0);

    Queue::assertPushed(SendAppointmentReminderJob::class, function ($job) use ($appointment) {
        return $job->appointmentId === $appointment->id && $job->isForced === false;
    });
});
