<?php

namespace Database\Factories\V1;

use App\Models\V1\Follow;
use App\Models\V1\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Follow>
 */
class FollowFactory extends Factory
{
    protected $model = Follow::class;

    public function definition(): array
    {
        return [
            'follower_id' => User::factory(),
            'followed_id' => User::factory(),
        ];
    }

    public function configure()
    {
        return $this->afterMaking(function (Follow $follow) {
            // Asegurar que el seguidor y el seguido sean diferentes
            if ($follow->follower_id === $follow->followed_id) {
                $follow->followed_id = User::factory()->create()->id;
            }
        });
    }
}
