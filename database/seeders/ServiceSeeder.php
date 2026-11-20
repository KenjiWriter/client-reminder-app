<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    /**
     * Cennik Face Modeling (Emilia Wiśniewska).
     * Każdy wariant czasu jest osobną usługą, bo rezerwacja liczy długość wizyty.
     */
    public function run(): void
    {
        $services = [
            [
                'name' => 'Masaż twarzy, szyi i obręczy barkowej',
                'category' => 'Twarz i żuchwa',
                'description' => null,
                'duration_minutes' => 90,
                'price' => 250,
            ],
            [
                'name' => 'Masaż twarzy kamieniem Oshin Bian Lift',
                'category' => 'Twarz i żuchwa',
                'description' => null,
                'duration_minutes' => 90,
                'price' => 250,
            ],
            [
                'name' => 'Masaż transbukalny (wewnątrzustny)',
                'category' => 'Twarz i żuchwa',
                'description' => null,
                'duration_minutes' => 60,
                'price' => 300,
            ],
            [
                'name' => 'Masaż transbukalny z masażem twarzy',
                'category' => 'Twarz i żuchwa',
                'description' => null,
                'duration_minutes' => 90,
                'price' => 380,
            ],
            [
                'name' => 'Masaż terapeutyczny – techniki dobierane indywidualnie',
                'category' => 'Masaż ciała',
                'description' => null,
                'duration_minutes' => 120,
                'price' => 350,
            ],
            [
                'name' => 'Masaż terapeutyczny – dłuższa wizyta',
                'category' => 'Masaż ciała',
                'description' => 'Ten sam masaż, dłuższy czas pracy.',
                'duration_minutes' => 180,
                'price' => 450,
            ],
            [
                'name' => 'Masaż terapeutyczny – najdłuższa wizyta',
                'category' => 'Masaż ciała',
                'description' => 'Ten sam masaż, najdłuższy czas pracy.',
                'duration_minutes' => 210,
                'price' => 550,
            ],
            [
                'name' => 'Masaż tkanek głębokich okolicy lędźwiowej',
                'category' => 'Masaż ciała',
                'description' => null,
                'duration_minutes' => 45,
                'price' => 110,
            ],
            [
                'name' => 'Masaż relaksacyjny całego ciała',
                'category' => 'Masaż ciała',
                'description' => null,
                'duration_minutes' => 120,
                'price' => 320,
            ],
            [
                'name' => 'Masaż ajurwedyjski całego ciała',
                'category' => 'Masaż ciała',
                'description' => null,
                'duration_minutes' => 180,
                'price' => 550,
            ],
            [
                'name' => 'Masaż dla kobiet w ciąży',
                'category' => 'Masaż ciała',
                'description' => null,
                'duration_minutes' => 90,
                'price' => 280,
            ],
            [
                'name' => 'Masaż dla kobiet w ciąży – dłuższa wizyta',
                'category' => 'Masaż ciała',
                'description' => 'Ten sam masaż, dłuższy czas pracy.',
                'duration_minutes' => 120,
                'price' => 350,
            ],
            [
                'name' => 'Drenaż limfatyczny całego ciała',
                'category' => 'Drenaż i kapilaroterapia',
                'description' => null,
                'duration_minutes' => 120,
                'price' => 420,
            ],
            [
                'name' => 'Kapilaroterapia – samodzielna wizyta',
                'category' => 'Drenaż i kapilaroterapia',
                'description' => null,
                'duration_minutes' => 60,
                'price' => 200,
            ],
            [
                'name' => 'Kapilaroterapia jako dodatek do masażu',
                'category' => 'Drenaż i kapilaroterapia',
                'description' => 'Dodatek do masażu, nie osobna wizyta. Wydłuża zabieg o 40 minut.',
                'duration_minutes' => 40,
                'price' => 150,
            ],
            [
                'name' => 'Oddech 9D – prowadzona sesja oddechowa',
                'category' => 'Sesja oddechowa',
                'description' => null,
                'duration_minutes' => 70,
                'price' => 200,
            ],
        ];

        foreach ($services as $service) {
            Service::updateOrCreate(
                ['name' => $service['name']],
                [
                    ...$service,
                    'max_price' => null,
                    'is_active' => true,
                ],
            );
        }

        Service::query()
            ->whereNotIn('name', array_column($services, 'name'))
            ->update(['is_active' => false]);
    }
}
