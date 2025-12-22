<?php

use App\Models\User;
use App\Models\SmsMessage;
use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guests are redirected to the login page from messages', function () {
    $response = $this->get(route('messages.index'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can see the messages log', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    SmsMessage::create([
        'provider' => 'log',
        'to_e164' => '+48123456789',
        'status' => 'success',
        'message_hash' => md5('test'),
        'sent_at' => now(),
    ]);

    $response = $this->get(route('messages.index'));
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Messages/Index')
        ->has('messages.data', 1)
        ->where('messages.data.0.to_e164', '+48123456789')
    );
});

test('it filters messages by search', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $client = Client::factory()->create(['full_name' => 'John Doe']);
    
    SmsMessage::create([
        'provider' => 'log',
        'to_e164' => '+48111222333',
        'status' => 'success',
        'message_hash' => md5('test1'),
        'sent_at' => now(),
        'client_id' => $client->id,
    ]);

    SmsMessage::create([
        'provider' => 'log',
        'to_e164' => '+48999888777',
        'status' => 'success',
        'message_hash' => md5('test2'),
        'sent_at' => now(),
    ]);

    // Search by name
    $response = $this->get(route('messages.index', ['search' => 'John']));
    $response->assertInertia(fn ($page) => $page
        ->has('messages.data', 1)
        ->where('messages.data.0.to_e164', '+48111222333')
    );

    // Search by phone
    $response = $this->get(route('messages.index', ['search' => '999']));
    $response->assertInertia(fn ($page) => $page
        ->has('messages.data', 1)
        ->where('messages.data.0.to_e164', '+48999888777')
    );
});

test('it filters messages by status', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    SmsMessage::create([
        'provider' => 'log',
        'to_e164' => '+48123456789',
        'status' => 'success',
        'message_hash' => md5('test1'),
        'sent_at' => now(),
    ]);

    SmsMessage::create([
        'provider' => 'log',
        'to_e164' => '+48987654321',
        'status' => 'failed',
        'message_hash' => md5('test2'),
        'sent_at' => now(),
    ]);

    $response = $this->get(route('messages.index', ['status' => 'failed']));
    $response->assertInertia(fn ($page) => $page
        ->has('messages.data', 1)
        ->where('messages.data.0.status', 'failed')
    );
});
