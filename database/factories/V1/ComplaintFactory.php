<?php

namespace Database\Factories\V1;

use App\Models\V1\Post;
use App\Models\V1\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ComplaintFactory extends Factory
{
    public function definition(): array
    {
        // Seleccionar aleatoriamente entre Post, Comment o ReplayComment
        $complaintableType = $this->faker->randomElement([
            Post::class,
            \App\Models\V1\Comment::class,
            \App\Models\V1\ReplayComment::class
        ]);

        $complaintable = $complaintableType::factory()->create();

        return [
            'description' => $this->faker->sentence,
            'complaintable_id' => $complaintable->id,
            'complaintable_type' => $complaintableType,
            'user_id' => User::factory()
        ];
    }
}
