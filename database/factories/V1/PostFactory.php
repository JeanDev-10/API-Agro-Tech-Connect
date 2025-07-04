<?php

namespace Database\Factories\V1;

use App\Models\V1\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        // Obtener usuarios existentes o crear uno nuevo
        $user = User::inRandomOrder()->first() ?? User::factory()->create();

        // Texto con posible emoji (1 de cada 3 posts tendrá emoji)
        $hasEmoji = $this->faker->boolean(33);
        $emoji = $hasEmoji ? ' ' . $this->faker->emoji() : '';

        return [
            'title' => $this->faker->sentence(3) . $emoji,
            'description' => $this->faker->paragraphs(3, true) . $emoji,
            'user_id' => $user->id,
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'updated_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }

    /**
     * Post con título muy largo (para testing)
     */
    public function longTitle()
    {
        return $this->state(function (array $attributes) {
            return [
                'title' => $this->faker->paragraph(1) . ' ' . $this->faker->emoji(),
            ];
        });
    }

    /**
     * Post sin descripción
     */
    public function emptyDescription()
    {
        return $this->state(function (array $attributes) {
            return [
                'description' => null,
            ];
        });
    }
}
