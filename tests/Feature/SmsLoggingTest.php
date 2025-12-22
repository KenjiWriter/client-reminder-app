<?php

use App\Jobs\SendAppointmentReminderJob;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\SmsMessage;
use App\Contracts\SmsProvider;
use App\ValueObjects\SmsResult;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('it logs successful sms send', function () {
    $client = Client::factory()->create(['phone_e164' => '+48123456789']);
    $appointment = Appointment::factory()->create([
        'client_id' => $client->id,
        'reminder_sent_at' => null,
    ]);

    $mockProvider = Mockery::mock(SmsProvider::class);
    $mockProvider->shouldReceive('send')
        ->once()
        ->andReturn(SmsResult::success('msg-123'));

    $job = new SendAppointmentReminderJob($appointment->id);
    $job->handle($mockProvider);

    $this->assertDatabaseHas('sms_messages', [
        'appointment_id' => $appointment->id,
        'client_id' => $client->id,
        'status' => 'success',
        'to_e164' => '+48123456789',
        'provider_message_id' => 'msg-123',
    ]);
});

test('it logs failed sms send', function () {
    $client = Client::factory()->create(['phone_e164' => '+48123456789']);
    $appointment = Appointment::factory()->create([
        'client_id' => $client->id,
        'reminder_sent_at' => null,
    ]);

    $mockProvider = Mockery::mock(SmsProvider::class);
    $mockProvider->shouldReceive('send')
        ->once()
        ->andReturn(SmsResult::failure('Provider error'));

    $job = new SendAppointmentReminderJob($appointment->id);
    
    try {
        $job->handle($mockProvider);
    } catch (\RuntimeException $e) {
        // Expected
    }

    $this->assertDatabaseHas('sms_messages', [
        'appointment_id' => $appointment->id,
        'client_id' => $client->id,
        'status' => 'failed',
        'error' => 'Provider error',
    ]);

    // Check that appointment was NOT marked as sent
    $appointment->refresh();
    expect($appointment->reminder_sent_at)->toBeNull();
});
