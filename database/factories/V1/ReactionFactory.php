<?php

namespace Database\Factories\V1;

use App\Models\V1\Comment;
use App\Models\V1\Post;
use App\Models\V1\ReplayComment;
use App\Models\V1\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        // Seleccionar aleatoriamente entre Post, Comment o ReplayComment
        $reactionableType = $this->faker->randomElement([
            Post::class,
            Comment::class,
            ReplayComment::class
        ]);

        // Obtener o crear una instancia del tipo seleccionado
        $reactionable = $reactionableType::inRandomOrder()->first() ?? $reactionableType::factory()->create();

        // Obtener usuario que no sea el autor del elemento
        $user = User::where('id', '!=', $reactionable->user_id)
                  ->inRandomOrder()
                  ->first() ?? User::factory()->create();

        return [
            'type' => $this->faker->randomElement(['positivo', 'negativo']),
            'user_id' => $user->id,
            'reactionable_id' => $reactionable->id,
            'reactionable_type' => $reactionableType,
            'created_at' => $this->faker->dateTimeBetween($reactionable->created_at, 'now'),
        ];
    }

    /**
     * Reacción positiva
     */
    public function positive()
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'positivo',
            ];
        });
    }

    /**
     * Reacción negativa
     */
    public function negative()
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'negativo',
            ];
        });
    }

    /**
     * Reacción del mismo usuario (para testing)
     */
    public function sameUser()
    {
        return $this->state(function (array $attributes) {
            $reactionable = $attributes['reactionable_type']::find($attributes['reactionable_id']);
            return [
                'user_id' => $reactionable->user_id,
            ];
        });
    }
}
