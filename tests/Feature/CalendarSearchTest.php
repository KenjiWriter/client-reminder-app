<?php

use App\Models\Appointment;
use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

uses(RefreshDatabase::class);

test('it searches appointments by client name', function () {
    $client = Client::factory()->create([
        'full_name' => 'John Doe',
        'email' => 'john@example.com',
        'phone_e164' => '+48123456789'
    ]);

    Appointment::factory()->create([
        'client_id' => $client->id,
        'starts_at' => Carbon::now()->addDay(),
    ]);

    $this->actingAs($user = \App\Models\User::factory()->create())
        ->getJson(route('appointments.search', ['query' => 'John']))
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonPath('0.client.name', 'John Doe');
});

test('it searches appointments by phone', function () {
    $client = Client::factory()->create([
        'full_name' => 'Jane Smith',
        'phone_e164' => '+48987654321'
    ]);

    Appointment::factory()->create([
        'client_id' => $client->id,
        'starts_at' => Carbon::now()->addDays(2),
    ]);

    $this->actingAs($user = \App\Models\User::factory()->create())
        ->getJson(route('appointments.search', ['query' => '987654']))
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonPath('0.client.name', 'Jane Smith');
});

test('it returns empty list for no matches', function () {
    $this->actingAs($user = \App\Models\User::factory()->create())
        ->getJson(route('appointments.search', ['query' => 'Nonexistent']))
        ->assertOk()
        ->assertJsonCount(0);
});

test('it returns empty list for short or empty query', function () {
    $this->actingAs($user = \App\Models\User::factory()->create())
        ->getJson(route('appointments.search', ['query' => '']))
        ->assertOk()
        ->assertJsonCount(0);
});
