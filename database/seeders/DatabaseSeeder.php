<?php

namespace Database\Seeders;

use Database\Seeders\V1\OldPasswordSeeder;
use Database\Seeders\V1\UserInformationSeeder;
use Database\Seeders\V1\UserSeeder;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        //call a seeder
        $this->call(UserSeeder::class);
        $this->call(UserInformationSeeder::class);
       /*   $this->call(OldPasswordSeeder::class); */
    }
}
