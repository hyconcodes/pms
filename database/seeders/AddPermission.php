<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class AddPermission extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Specialization permissions
        // Permission::create(['name' => 'view.specializations']);
        // Permission::create(['name' => 'create.specializations']);
        // Permission::create(['name' => 'edit.specializations']);
        // Permission::create(['name' => 'delete.specializations']);
        // Permission::create(['name' => 'view.appointments']);
        // Permission::create(['name' => 'approve.appointments']);
        // Permission::create(['name' => 'view.meds']);
        // Permission::create(['name' => 'create.meds']);
        // Permission::create(['name' => 'edit.meds']);
        // Permission::create(['name' => 'delete.meds']);
        // Permission::create(['name' => 'view.prescription']);
        // Permission::create(['name' => 'give.prescription']);
        Permission::create(['name' => 'accept.payment']);
    }
}
