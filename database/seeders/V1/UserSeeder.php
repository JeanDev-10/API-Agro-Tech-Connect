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
        $admin = User::create([
            'name' => 'Admin',
            'lastname' => 'System',
            'username' => 'admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('Admin123@'),
            'registration_method' => 'local',
            'email_verified_at' => now(),
        ]);
        Role::create(["name" => "admin"]);
        Role::create(["name" => "client"]);
        $admin->assignRole("admin");
         User::factory()->count(10)->create()->each(function ($user) {
            $user->assignRole('client');
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
