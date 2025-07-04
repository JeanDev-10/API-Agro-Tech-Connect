<?php

namespace Database\Factories\V1;

use App\Models\V1\Post;
use App\Models\V1\User;
use App\Models\V1\Comment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CommentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        // Obtener posts y usuarios existentes o crear nuevos
        $post = Post::inRandomOrder()->first() ?? Post::factory()->create();
        $user = User::inRandomOrder()->first() ?? User::factory()->create();

        // 30% de probabilidad de incluir emoji
        $hasEmoji = $this->faker->boolean(30);
        $emoji = $hasEmoji ? ' ' . $this->faker->emoji() : '';

        return [
            'comment' => $this->faker->paragraph() . $emoji,
            'post_id' => $post->id,
            'user_id' => $user->id,
            'created_at' => $this->faker->dateTimeBetween($post->created_at, 'now'),
            'updated_at' => $this->faker->dateTimeBetween($post->created_at, 'now'),
        ];
    }

    /**
     * Comentario largo (para testing)
     */
    public function longComment()
    {
        return $this->state(function (array $attributes) {
            return [
                'comment' => $this->faker->paragraphs(5, true) . ' ' . $this->faker->emoji(),
            ];
        });
    }

    /**
     * Comentario con respuesta (para crear hilos)
     */
    public function withReplies()
    {
        return $this->afterCreating(function (Comment $comment) {
            \App\Models\V1\ReplayComment::factory()
                ->count($this->faker->numberBetween(1, 3))
                ->create(['comment_id' => $comment->id]);
        });
    }
}
