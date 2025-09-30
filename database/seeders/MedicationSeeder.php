<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MedicationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('medications')->insert([
            [
                'name' => 'Lisinopril 10mg',
                'status' => 'In Stock',
                'stock_level' => 150,
                'expiry' => '2025-12-31',
                'supplier' => 'PharmaCorp',
            ],
            [
                'name' => 'Metformin 500mg',
                'status' => 'In Stock',
                'stock_level' => 200,
                'expiry' => '2025-11-30',
                'supplier' => 'MediSupply',
            ],
            [
                'name' => 'Atorvastatin 20mg',
                'status' => 'Low Stock',
                'stock_level' => 30,
                'expiry' => '2025-10-15',
                'supplier' => 'HealthGen',
            ],
            [
                'name' => 'Amlodipine 5mg',
                'status' => 'In Stock',
                'stock_level' => 100,
                'expiry' => '2026-01-20',
                'supplier' => 'PharmaCorp',
            ],
            [
                'name' => 'Omeprazole 20mg',
                'status' => 'Out of Stock',
                'stock_level' => 0,
                'expiry' => '2025-09-05',
                'supplier' => 'MediSupply',
            ],
            [
                'name' => 'Losartan 50mg',
                'status' => 'In Stock',
                'stock_level' => 120,
                'expiry' => '2025-12-15',
                'supplier' => 'HealthGen',
            ],
            [
                'name' => 'Simvastatin 40mg',
                'status' => 'In Stock',
                'stock_level' => 90,
                'expiry' => '2025-11-10',
                'supplier' => 'PharmaCorp',
            ],
            [
                'name' => 'Hydrochlorothiazide 25mg',
                'status' => 'Low Stock',
                'stock_level' => 25,
                'expiry' => '2025-10-30',
                'supplier' => 'MediSupply',
            ],
            [
                'name' => 'Metoprolol 100mg',
                'status' => 'In Stock',
                'stock_level' => 80,
                'expiry' => '2026-02-28',
                'supplier' => 'HealthGen',
            ],
            [
                'name' => 'Gabapentin 300mg',
                'status' => 'In Stock',
                'stock_level' => 110,
                'expiry' => '2025-12-01',
                'supplier' => 'PharmaCorp',
            ],
            [
                'name' => 'Levothyroxine 50mcg',
                'status' => 'Out of Stock',
                'stock_level' => 0,
                'expiry' => '2025-08-20',
                'supplier' => 'MediSupply',
            ],
            [
                'name' => 'Aspirin 81mg',
                'status' => 'In Stock',
                'stock_level' => 300,
                'expiry' => '2025-11-05',
                'supplier' => 'HealthGen',
            ],
            [
                'name' => 'Ibuprofen 200mg',
                'status' => 'In Stock',
                'stock_level' => 250,
                'expiry' => '2025-10-25',
                'supplier' => 'PharmaCorp',
            ],
            [
                'name' => 'Acetaminophen 500mg',
                'status' => 'Low Stock',
                'stock_level' => 40,
                'expiry' => '2025-09-30',
                'supplier' => 'MediSupply',
            ],
            [
                'name' => 'Prednisone 10mg',
                'status' => 'In Stock',
                'stock_level' => 60,
                'expiry' => '2026-03-15',
                'supplier' => 'HealthGen',
            ],
            [
                'name' => 'Warfarin 5mg',
                'status' => 'In Stock',
                'stock_level' => 70,
                'expiry' => '2025-12-10',
                'supplier' => 'PharmaCorp',
            ],
            [
                'name' => 'Albuterol 90mcg',
                'status' => 'Out of Stock',
                'stock_level' => 0,
                'expiry' => '2025-07-15',
                'supplier' => 'MediSupply',
            ],
            [
                'name' => 'Fluticasone 50mcg',
                'status' => 'In Stock',
                'stock_level' => 95,
                'expiry' => '2025-11-20',
                'supplier' => 'HealthGen',
            ],
            [
                'name' => 'Sertraline 50mg',
                'status' => 'In Stock',
                'stock_level' => 130,
                'expiry' => '2025-12-25',
                'supplier' => 'PharmaCorp',
            ],
            [
                'name' => 'Cetirizine 10mg',
                'status' => 'Low Stock',
                'stock_level' => 35,
                'expiry' => '2025-10-05',
                'supplier' => 'MediSupply',
            ],
            [
                'name' => 'Montelukast 10mg',
                'status' => 'In Stock',
                'stock_level' => 85,
                'expiry' => '2026-01-10',
                'supplier' => 'HealthGen',
            ],
            [
                'name' => 'Pantoprazole 40mg',
                'status' => 'In Stock',
                'stock_level' => 75,
                'expiry' => '2025-11-15',
                'supplier' => 'PharmaCorp',
            ],
            [
                'name' => 'Furosemide 40mg',
                'status' => 'Out of Stock',
                'stock_level' => 0,
                'expiry' => '2025-08-10',
                'supplier' => 'MediSupply',
            ],
            [
                'name' => 'Carvedilol 12.5mg',
                'status' => 'In Stock',
                'stock_level' => 55,
                'expiry' => '2025-12-20',
                'supplier' => 'HealthGen',
            ],
            [
                'name' => 'Tamsulosin 0.4mg',
                'status' => 'In Stock',
                'stock_level' => 65,
                'expiry' => '2026-02-10',
                'supplier' => 'PharmaCorp',
            ],
        ]);
    }
}
