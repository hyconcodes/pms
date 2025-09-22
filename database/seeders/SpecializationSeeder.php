<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Specialization;
class SpecializationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $specializations = [
            [
                'name' => 'Cardiology',
                'description' => 'Deals with disorders of the heart and blood vessels'
            ],
            [
                'name' => 'Dermatology',
                'description' => 'Focuses on conditions affecting the skin, hair, and nails'
            ],
            [
                'name' => 'Neurology',
                'description' => 'Treats disorders of the nervous system'
            ],
            [
                'name' => 'Pediatrics',
                'description' => 'Provides medical care for infants, children, and adolescents'
            ],
            [
                'name' => 'Orthopedics',
                'description' => 'Focuses on conditions affecting the musculoskeletal system'
            ],
            [
                'name' => 'Psychiatry',
                'description' => 'Deals with mental, emotional, and behavioral disorders'
            ],
            [
                'name' => 'Ophthalmology',
                'description' => 'Specializes in eye and vision care'
            ],
            [
                'name' => 'ENT',
                'description' => 'Treats ear, nose, and throat conditions'
            ]
        ];

        foreach ($specializations as $specialization) {
            Specialization::create($specialization);
        }
    }
}
