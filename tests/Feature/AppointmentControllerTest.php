<?php

use App\Models\Appointment;
use App\Models\Client;
use App\Models\User;
use Carbon\Carbon;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('can view calendar', function () {
    Appointment::factory()->create(['starts_at' => now()]);

    $response = $this->get(route('calendar.index'));

    $response->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Calendar/Index')
            ->has('events', 1)
        );
});

test('can schedule appointment', function () {
    $client = Client::factory()->create();

    $response = $this->post(route('appointments.store'), [
        'client_id' => $client->id,
        'starts_at' => now()->addDay()->toIso8601String(), // This will be ignored by controller as it merges date+time? No, form helper does that.
        // Wait, the controller just validates starts_at.
        // My test needs to mimic the form submission or the direct API.
        // The StoreRequest expects 'starts_at' as date.
        // The Vue form constructs 'starts_at' from date+time fields before submission.
        // So here we send 'starts_at'.
        'starts_at' => '2025-01-01 10:00:00',
        'duration_minutes' => 60,
        'note' => 'Checkup',
        'send_reminder' => true,
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect();

    $this->assertDatabaseHas('appointments', [
        'client_id' => $client->id,
        'note' => 'Checkup',
    ]);
});

test('can cancel appointment', function () {
    $appointment = Appointment::factory()->create();

    $response = $this->delete(route('appointments.destroy', $appointment));

    $response->assertRedirect();
    $this->assertDatabaseMissing('appointments', ['id' => $appointment->id]);
});
