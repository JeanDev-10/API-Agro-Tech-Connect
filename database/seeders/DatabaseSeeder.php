<?php

namespace Database\Seeders;

use Database\Seeders\V1\CommentSeeder;
use Database\Seeders\V1\ComplaintSeeder;
use Database\Seeders\V1\FollowSeeder;
use Database\Seeders\V1\ImageSeeder;
use Database\Seeders\V1\OldPasswordSeeder;
use Database\Seeders\V1\PostSeeder;
use Database\Seeders\V1\RangeSeeder;
use Database\Seeders\V1\RangeUserSeeder;
use Database\Seeders\V1\ReactionSeeder;
use Database\Seeders\V1\ReplayCommentSeeder;
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
        $this->call(RangeSeeder::class);
        $this->call(RangeUserSeeder::class);
        $this->call(OldPasswordSeeder::class);
        $this->call(FollowSeeder::class);
        $this->call(PostSeeder::class);
        $this->call(CommentSeeder::class);
        $this->call(ReplayCommentSeeder::class);
        $this->call(ImageSeeder::class);
        $this->call(ReactionSeeder::class);
        $this->call(ComplaintSeeder::class);

    }
}
