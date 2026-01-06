<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $services = [
            [
                'name' => 'Masaż Kobido',
                'description' => 'Japoński masaż liftingujący twarzy',
                'duration_minutes' => 90,
                'price' => 350.00,
                'is_active' => true,
            ],
            [
                'name' => 'Masaż Transbukalny',
                'description' => 'Wewnątrzustny masaż odmładzający',
                'duration_minutes' => 60,
                'price' => 250.00,
                'is_active' => true,
            ],
            [
                'name' => 'Lifting twarzy',
                'description' => 'Zabieg ujędrniający i liftingujący',
                'duration_minutes' => 75,
                'price' => 300.00,
                'is_active' => true,
            ],
            [
                'name' => 'Masaż Shiatsu twarzy',
                'description' => 'Technika punktowego nacisku',
                'duration_minutes' => 60,
                'price' => 220.00,
                'is_active' => true,
            ],
        ];

        foreach ($services as $service) {
            Service::create($service);
        }
    }
}
