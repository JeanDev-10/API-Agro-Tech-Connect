<?php

namespace Database\Seeders\V1;

use App\Models\V1\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    public function run()
    {
        // Usuario admin
        $admin = User::create([
            'name' => "Frowen",
            'lastname' => 'Sacón',
            'username' => 'FrowenS10',
            'email' => "agrotechconnect2025@gmail.com",
            'password' => bcrypt("R7pwA3vLz!9eQ"),
            'registration_method' => 'local',
            'email_verified_at' => now(),
        ]);

        // Crear rol admin y permisos básicos que necesita
        $admin_role = Role::create(["name" => "admin"]);

        // Crear los permisos que el admin necesita
        Permission::firstOrCreate(["name" => "user.change-password"]);
        Permission::firstOrCreate(["name" => "user.upload-avatar"]);
        Permission::firstOrCreate(["name" => "admin.delete-account"]);

        $admin->assignRole("admin");
        $admin_role->syncPermissions(['user.change-password', 'user.upload-avatar', 'admin.delete-account']);
    }
}