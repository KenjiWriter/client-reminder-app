<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@admin.com',
            'password' => \Illuminate\Support\Facades\Hash::make('secret'),
        ]);

        // Seed medical condition types
        $this->call(MedicalConditionTypeSeeder::class);
        
        // Seed default settings
        $this->call(SettingsSeeder::class);
        
        // Seed default services
        $this->call(ServiceSeeder::class);
    }
}
