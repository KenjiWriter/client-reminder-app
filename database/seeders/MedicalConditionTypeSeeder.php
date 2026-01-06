<?php

namespace Database\Seeders;

use App\Models\MedicalConditionType;
use Illuminate\Database\Seeder;

class MedicalConditionTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $conditions = [
            // Contraindications (High Risk - Red Badges)
            [
                'name' => 'Ciąża',
                'category' => 'contraindication',
                'severity' => 'high',
                'requires_date' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Epilepsja',
                'category' => 'contraindication',
                'severity' => 'high',
                'requires_date' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Nowotwór',
                'category' => 'contraindication',
                'severity' => 'high',
                'requires_date' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Opryszczka',
                'category' => 'contraindication',
                'severity' => 'medium',
                'requires_date' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Problemy z tarczycą',
                'category' => 'contraindication',
                'severity' => 'medium',
                'requires_date' => false,
                'is_active' => true,
            ],

            // Esthetic Procedures (Medium Risk - Yellow/Info Badges)
            [
                'name' => 'Botox',
                'category' => 'esthetic',
                'severity' => 'medium',
                'requires_date' => true, // Show date picker
                'is_active' => true,
            ],
            [
                'name' => 'Wypełniacze',
                'category' => 'esthetic',
                'severity' => 'medium',
                'requires_date' => true, // Show date picker
                'is_active' => true,
            ],
            [
                'name' => 'Nici liftingujące',
                'category' => 'esthetic',
                'severity' => 'medium',
                'requires_date' => false,
                'is_active' => true,
            ],
        ];

        foreach ($conditions as $condition) {
            MedicalConditionType::create($condition);
        }
    }
}
