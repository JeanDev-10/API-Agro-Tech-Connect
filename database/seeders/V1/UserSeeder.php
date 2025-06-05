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
            'name' => 'Admin',
            'lastname' => 'System',
            'username' => 'admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('Admin123@'),
            'registration_method' => 'local',
            'email_verified_at' => now(),
        ]);
        $admin_role = Role::create(["name" => "admin"]);
        $client_role = Role::create(["name" => "client"]);
        $client_role_social = Role::create(["name" => "client_social"]);
        Permission::firstOrCreate(["name" => "user.change-password"]);
        Permission::firstOrCreate(["name" => "user.delete-account"]);
        Permission::firstOrCreate(["name" => "user.delete-account-social"]);
        Permission::firstOrCreate(["name" => "user.upload-avatar"]);
        Permission::firstOrCreate(["name" => "post.create-complaint"]);
        Permission::firstOrCreate(["name" => "comment.create-complaint"]);
        Permission::firstOrCreate(["name" => "replyComment.create-complaint"]);
        Permission::firstOrCreate(["name" => "admin.delete-account"]);
        $admin->assignRole("admin");
        $admin_role->syncPermissions(['user.change-password','user.upload-avatar','admin.delete-account']);
        $client_role->syncPermissions(['user.change-password', 'user.upload-avatar','post.create-complaint','comment.create-complaint','replyComment.create-complaint','user.delete-account']);
        $client_role_social->syncPermissions(['user.delete-account-social','post.create-complaint','comment.create-complaint','replyComment.create-complaint']);

        User::factory()->count(10)->create()->each(function ($user) {
            if ($user->registration_method == 'local')
                $user->assignRole('client');
            else {
                $user->assignRole('client_social');
            }
        });
    }

}
