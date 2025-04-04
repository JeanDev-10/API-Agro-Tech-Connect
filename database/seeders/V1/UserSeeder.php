<?php

namespace Database\Seeders\V1;

use App\Models\V1\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    public function run()
    {
        // Usuario admin
        $admin=User::create([
            'name' => 'Admin',
            'lastname' => 'System',
            'username' => 'admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('Admin123@'),
            'registration_method' => 'local',
            'email_verified_at' => now(),
        ]);
        $role_admin=Role::create(["name"=>"admin"]);
        Role::create(["name"=>"client"]);
        $admin->assignRole("admin");
        User::factory()->count(50)->create()->each(function ($user) {
            $user->assignRole('client');
        }); 
    }
}