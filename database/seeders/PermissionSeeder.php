<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Role management permissions
        $rolePermissions = [
            'view.roles',
            'create.roles',
            'edit.roles',
            'delete.roles',
            'assign.permissions',
        ];

        // Patient management permissions
        $patientPermissions = [
            'view.patients',
            'create.patients',
            'edit.patients',
            'delete.patients',
        ];

        // Medical record permissions
        $medicalRecordPermissions = [
            'view.medical.records',
            'create.medical.records',
            'edit.medical.records',
            'delete.medical.records',
        ];

        //staff permissions
        $staffPermissions = [
            'view.staff',
            'create.staff',
            'edit.staff',
            'delete.staff',
        ];

        // Combine all permissions
        $permissions = array_merge(
            $rolePermissions,
            $patientPermissions,
            $medicalRecordPermissions,
            $staffPermissions
        );

        // Create permissions in the database
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }
    }
}