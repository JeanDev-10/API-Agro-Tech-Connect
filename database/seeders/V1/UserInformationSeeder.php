<?php

namespace Database\Seeders\V1;

use App\Models\V1\User;
use App\Models\V1\UserInformation;
use Illuminate\Database\Seeder;

class UserInformationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear información para usuarios existentes
        $users = User::all();

        foreach ($users as $user) {
            UserInformation::factory()->create([
                'user_id' => $user->id,
            ]);
        }
    }
}
