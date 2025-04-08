<?php

namespace Database\Factories\V1;

use App\Models\V1\User;
use App\Models\V1\UserInformation;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserInformationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = UserInformation::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'description' => $this->faker->realText(100) . ' 😊👍', // Emojis añadidos
            'link1' => $this->faker->url(),
            'link2' => $this->faker->url(),
            'link3' => $this->faker->url(),
            'user_id' => User::factory(),
        ];
    }
}
