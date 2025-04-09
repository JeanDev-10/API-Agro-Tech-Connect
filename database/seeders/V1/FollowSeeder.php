<?php

namespace Database\Seeders\V1;

use App\Models\V1\Follow;
use App\Models\V1\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FollowSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::where('id', '!=', 1)->get(); // Excluir al admin (id=1)

        if ($users->count() < 2) {
            User::factory()->count(10)->create();
            $users = User::where('id', '!=', 1)->get();
        }

        $users->each(function ($user) use ($users) {
            // Cada usuario seguirá a 3-5 usuarios aleatorios
            $toFollow = $users->where('id', '!=', $user->id) // No seguirse a sí mismo
                ->random(rand(3, 5));

            $toFollow->each(function ($followed) use ($user) {
                Follow::firstOrCreate([
                    'follower_id' => $user->id,
                    'followed_id' => $followed->id
                ]);
            });
        });

        // Asegurar que el admin tenga seguidores
        $admin = User::find(1);
        if ($admin) {
            $followers = $users->random(rand(3, 5));
            $followers->each(function ($follower) use ($admin) {
                Follow::firstOrCreate([
                    'follower_id' => $follower->id,
                    'followed_id' => $admin->id
                ]);
            });
        }
    }
}
