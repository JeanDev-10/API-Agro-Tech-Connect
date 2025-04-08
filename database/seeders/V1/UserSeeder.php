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
        $admin_role=Role::create(["name" => "admin"]);
        $client_role=Role::create(["name" => "client"]);
        $client_role_social=Role::create(["name" => "client_social"]);
        Permission::firstOrCreate(["name"=>"user.change-password"]);
        Permission::firstOrCreate(["name"=>"user.delete-account"]);
        Permission::firstOrCreate(["name"=>"user.delete-account-social"]);
        $admin->assignRole("admin");
        $admin_role->syncPermissions(Permission::all());
        $client_role->syncPermissions(['user.change-password','user.delete-account']);
        $client_role_social->syncPermissions(['user.delete-account-social']);

         User::factory()->count(10)->create()->each(function ($user) {
            if($user->registration_method == 'local')
                $user->assignRole('client');
            else{
                $user->assignRole('client_social');
            }
            $user->image()->create([
                'url' => $this->generateRandomAvatar($user->email),
                'image_uuid' => \Illuminate\Support\Str::uuid(),
            ]);
        });
    }
    protected function generateRandomAvatar(string $seed): string
    {
        $styles = ['adventurer', 'avataaars', 'big-ears', 'bottts', 'croodles'];
        $style = $styles[rand(0, count($styles) - 1)];

        return "https://avatars.dicebear.com/api/{$style}/{$seed}.svg";
    }
}
