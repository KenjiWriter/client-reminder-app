<?php

use App\Models\Appointment;
use App\Models\Client;
use App\Models\SmsMessage;
use App\Services\AppointmentReminderSender;
use App\ValueObjects\SmsResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Lang;

uses(RefreshDatabase::class);

test('it retries with no-link template when links are restricted', function () {
    $client = Client::factory()->create(['phone_e164' => '+48123456789']);
    $appointment = Appointment::factory()->create([
        'client_id' => $client->id,
        'starts_at' => now()->addDay(),
        'send_reminder' => true,
        'reminder_sent_at' => null,
    ]);

    // Mock SmsProvider
    $mock = Mockery::mock(\App\Contracts\SmsProvider::class);

    // First call: fails with specific error
    // We expect the original message to contain a link (e.g. http key or just checking it matches the template with link)
    $mock->shouldReceive('send')
        ->once()
        ->withArgs(function ($to, $message) {
            // Check content if needed, but primarily we want to return the specific error
            return $to === '+48123456789'; 
        })
        ->andReturn(SmsResult::failure('Not allowed to send messages with link'));

    // Second call: succeeds, message should be the _no_link version
    $mock->shouldReceive('send')
        ->once()
        ->withArgs(function ($to, $message) {
            // Ideally we check if it DOES NOT contain the link or matches the new template
            // The no_link template for appointment_reminder ends with "Prosimy o obecność."
            return $to === '+48123456789' && str_contains($message, 'Prosimy o obecność'); 
        })
        ->andReturn(SmsResult::success('msg-id-123'));

    app()->instance(\App\Contracts\SmsProvider::class, $mock);

    $sender = app(AppointmentReminderSender::class);
    $result = $sender->send($appointment, true); // Force to bypass time/sent checks

    expect($result->success)->toBeTrue();
    expect($result->providerMessageId)->toBe('msg-id-123');
    
    // START DB CHECK
    // Should have 2 logs: 1 failure, 1 success
    $logs = SmsMessage::where('appointment_id', $appointment->id)->orderBy('id')->get();
    expect($logs)->toHaveCount(2);
    expect($logs[0]->status)->toBe('failed');
    expect($logs[0]->error)->toContain('Not allowed to send messages with link');
    expect($logs[1]->status)->toBe('success');
    // END DB CHECK
});

test('it does not retry on other errors', function () {
    $client = Client::factory()->create(['phone_e164' => '+48123456789']);
    $appointment = Appointment::factory()->create([
        'client_id' => $client->id,
        'starts_at' => now()->addDay(),
        'send_reminder' => true,
    ]);

    $mock = Mockery::mock(\App\Contracts\SmsProvider::class);
    $mock->shouldReceive('send')
        ->once()
        ->andReturn(SmsResult::failure('Some other error'));

    app()->instance(\App\Contracts\SmsProvider::class, $mock);

    $sender = app(AppointmentReminderSender::class);
    $result = $sender->send($appointment, true);

    expect($result->success)->toBeFalse();
    expect($result->error)->toBe('Some other error');
    
    $logs = SmsMessage::where('appointment_id', $appointment->id)->get();
    expect($logs)->toHaveCount(1);
    expect($logs[0]->status)->toBe('failed');
});
