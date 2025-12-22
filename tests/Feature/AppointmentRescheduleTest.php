<?php

use App\Models\Appointment;
use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('it tracks reschedules when starts_at changes', function () {
    $client = Client::factory()->create();
    $appointment = Appointment::factory()->create([
        'client_id' => $client->id,
        'starts_at' => now()->addDay(),
    ]);

    expect($appointment->rescheduled_count)->toBe(0);
    expect($appointment->first_rescheduled_at)->toBeNull();
    expect($appointment->last_rescheduled_at)->toBeNull();

    // Change note - should NOT trigger reschedule
    $appointment->update(['note' => 'updated note']);
    $appointment->refresh();

    expect($appointment->rescheduled_count)->toBe(0);

    // Change starts_at - SHOULD trigger reschedule
    $newTime = now()->addDays(2);
    $appointment->update(['starts_at' => $newTime]);
    $appointment->refresh();

    expect($appointment->rescheduled_count)->toBe(1);
    expect($appointment->first_rescheduled_at)->not->toBeNull();
    expect($appointment->last_rescheduled_at)->not->toBeNull();
    expect($appointment->last_rescheduled_at->timestamp)->toBe($appointment->first_rescheduled_at->timestamp);

    // Change starts_at again
    sleep(1); // Ensure timestamp difference
    $anotherTime = now()->addDays(3);
    $appointment->update(['starts_at' => $anotherTime]);
    $appointment->refresh();

    expect($appointment->rescheduled_count)->toBe(2);
    expect($appointment->last_rescheduled_at->timestamp)->toBeGreaterThan($appointment->first_rescheduled_at->timestamp);
});
