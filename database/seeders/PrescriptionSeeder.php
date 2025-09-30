<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class PrescriptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('prescriptions')->insert([
            [
                'appointment_id' => 5,
                'medication_id' => 6,
                'quantity' => '30 tablets',
                'instructions' => 'Take once daily with food',
                'prescribed_date' => '2024-01-15',
            ],
            [
                'appointment_id' => 11,
                'medication_id' => 2,
                'quantity' => '60 capsules',
                'instructions' => 'Take twice daily, morning and evening',
                'prescribed_date' => '2024-01-16',
            ],
            [
                'appointment_id' => 13,
                'medication_id' => 3,
                'quantity' => '15 ml',
                'instructions' => 'Apply thin layer to affected area twice daily',
                'prescribed_date' => '2024-01-17',
            ],
            [
                'appointment_id' => 16,
                'medication_id' => 4,
                'quantity' => '10 patches',
                'instructions' => 'Apply one patch every 72 hours',
                'prescribed_date' => '2024-01-18',
            ],
            [
                'appointment_id' => 21,
                'medication_id' => 5,
                'quantity' => '100 tablets',
                'instructions' => 'Take as needed for pain, max 4 per day',
                'prescribed_date' => '2024-01-19',
            ],
        ]);
    }
}
