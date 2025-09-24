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
        // Crear roles primero
        $admin_role = Role::firstOrCreate(["name" => "admin"]);
        $client_role = Role::firstOrCreate(["name" => "client"]);
        $client_role_social = Role::firstOrCreate(["name" => "client_social"]);

        // Crear permisos
        Permission::firstOrCreate(["name" => "user.change-password"]);
        Permission::firstOrCreate(["name" => "user.upload-avatar"]);
        Permission::firstOrCreate(["name" => "admin.delete-account"]);
        Permission::firstOrCreate(["name" => "user.delete-account"]);
        Permission::firstOrCreate(["name" => "user.delete-account-social"]);
        Permission::firstOrCreate(["name" => "post.create-complaint"]);
        Permission::firstOrCreate(["name" => "comment.create-complaint"]);
        Permission::firstOrCreate(["name" => "replyComment.create-complaint"]);

        // Usuario admin
        $admin = User::firstOrCreate(
            ['email' => "agrotechconnect2025@gmail.com"],
            [
                'name' => "Frowen",
                'lastname' => 'Sacón',
                'username' => 'FrowenS10',
                'password' => bcrypt("R7pwA3vLz!9eQ"),
                'registration_method' => 'local',
                'email_verified_at' => now(),
            ]
        );

        // Asignar permisos a roles
        $admin_role->syncPermissions(['user.change-password', 'user.upload-avatar', 'admin.delete-account']);
        $client_role->syncPermissions(['user.change-password', 'user.upload-avatar', 'post.create-complaint', 'comment.create-complaint', 'replyComment.create-complaint', 'user.delete-account']);
        $client_role_social->syncPermissions(['user.delete-account-social', 'post.create-complaint', 'comment.create-complaint', 'replyComment.create-complaint']);

        // Asignar rol al admin
        if (!$admin->hasRole('admin')) {
            $admin->assignRole("admin");
        }
    }
}