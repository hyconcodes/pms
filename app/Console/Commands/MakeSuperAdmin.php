<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class MakeSuperAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:super-admin {email}'; 

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign super-admin role to a user by email';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            $this->error("User with email {$email} not found.");
            return 1;
        }
        
        // Check if super-admin role exists, create if not
        $superAdminRole = Role::where('name', 'super-admin')->first();
        $allPermissions = Permission::all();

        if (!$superAdminRole) {
            $superAdminRole = Role::create(['name' => 'super-admin']);
            $this->info('Created super-admin role.');
        }
        
        // Assign role to user
        $user->assignRole('super-admin');
        $superAdminRole->givePermissionTo($allPermissions);
        
        $this->info("User {$email} has been assigned the super-admin role.");
        
        return 0;
    }
}