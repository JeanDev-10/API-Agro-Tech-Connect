<?php

namespace Database\Seeders\V1;

use App\Models\V1\OldPassword;
use App\Models\V1\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OldPasswordSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear 3 passwords antiguos para cada usuario
        User::where('id','<>',1)->get()->each(function ($user) {
            OldPassword::factory(3)->create([
                'user_id' => $user->id
            ]);
        });
    }
}
