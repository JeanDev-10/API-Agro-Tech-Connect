<?php

namespace Database\Factories\V1;

use App\Models\V1\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Image>
 */
class ImageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        // Usar avatares aleatorios de DiceBear para imágenes de prueba
        $avatarStyles = ['adventurer', 'avataaars', 'big-ears', 'bottts', 'croodles'];
        $style = $this->faker->randomElement($avatarStyles);
        $seed = $this->faker->userName;

        return [
            'url' => "https://avatars.dicebear.com/api/{$style}/{$seed}.svg",
            'image_Uuid' => $this->faker->uuid,
            'imageable_id' => User::factory(),
            'imageable_type' => User::class,
        ];
    }
}
