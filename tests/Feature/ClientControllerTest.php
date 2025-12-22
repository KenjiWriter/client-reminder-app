<?php

use App\Models\Client;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('can list clients', function () {
    Client::factory()->count(3)->create();

    $response = $this->get(route('clients.index'));

    $response->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Clients/Index')
            ->has('clients.data', 3)
        );
});

test('can create client with phone normalization', function () {
    $response = $this->post(route('clients.store'), [
        'full_name' => 'John Doe',
        'phone' => '123 456 789', // Should become +48...
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('clients.index'));

    $this->assertDatabaseHas('clients', [
        'full_name' => 'John Doe',
        'phone_e164' => '+48123456789',
    ]);
});

test('can update client', function () {
    $client = Client::factory()->create(['full_name' => 'Old Name']);

    $response = $this->put(route('clients.update', $client), [
        'full_name' => 'New Name',
        'phone' => '+48123456789',
    ]);

    $response->assertRedirect();
    
    $this->assertDatabaseHas('clients', [
        'id' => $client->id,
        'full_name' => 'New Name',
    ]);
});

test('can delete client', function () {
    $client = Client::factory()->create();

    $response = $this->delete(route('clients.destroy', $client));

    $response->assertRedirect(route('clients.index'));

    $this->assertDatabaseMissing('clients', [
        'id' => $client->id,
    ]);
});
