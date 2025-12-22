<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Appointment>
 */
class AppointmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => \App\Models\Client::factory(),
            'starts_at' => fake()->dateTimeBetween('now', '+1 week'),
            'duration_minutes' => 60,
            'note' => fake()->sentence(),
            'send_reminder' => true,
            'reminder_sent_at' => null,
        ];
    }
}
