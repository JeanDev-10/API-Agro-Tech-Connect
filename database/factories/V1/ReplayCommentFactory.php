<?php

namespace Database\Factories\V1;

use App\Models\V1\Comment;
use App\Models\V1\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ReplayCommentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        // Obtener comentario existente o crear uno nuevo
        $comment = Comment::inRandomOrder()->first() ?? Comment::factory()->create();

        // Obtener usuario existente (excluyendo al autor del comentario original)
        $user = User::where('id', '!=', $comment->user_id)
                  ->inRandomOrder()
                  ->first() ?? User::factory()->create();

        // 25% de probabilidad de incluir emoji
        $hasEmoji = $this->faker->boolean(25);
        $emoji = $hasEmoji ? ' ' . $this->faker->emoji() : '';

        return [
            'comment' => $this->faker->sentence(10) . $emoji,
            'comment_id' => $comment->id,
            'user_id' => $user->id,
            'created_at' => $this->faker->dateTimeBetween($comment->created_at, 'now'),
            'updated_at' => $this->faker->dateTimeBetween($comment->created_at, 'now'),
        ];
    }

    /**
     * Respuesta larga (para testing)
     */
    public function longReply()
    {
        return $this->state(function (array $attributes) {
            return [
                'comment' => $this->faker->paragraphs(3, true) . ' ' . $this->faker->emoji(),
            ];
        });
    }

    /**
     * Respuesta del mismo usuario (discusión)
     */
    public function sameUser()
    {
        return $this->state(function (array $attributes) {
            $comment = Comment::find($attributes['comment_id']);
            return [
                'user_id' => $comment->user_id,
            ];
        });
    }
}
