<?php

namespace Database\Seeders\V1;

use App\Models\V1\Post;
use App\Models\V1\User;
use Illuminate\Database\Seeder;

class PostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Obtener todos los usuarios
        $users = User::all();

        // Si no hay usuarios, crear algunos
        if ($users->isEmpty()) {
            $users = User::factory()->count(5)->create();
        }

        // Crear 20 posts normales
        Post::factory()->count(20)->create();

        // Crear algunos posts especiales para testing
        Post::factory()->longTitle()->create([
            'user_id' => $users->random()->id,
            'description' => 'Este es un post con un título extremadamente largo para probar nuestros límites. '
                           . '¿Cómo manejará la base de datos este caso extremo? 🤔'
        ]);

        Post::factory()->emptyDescription()->create([
            'user_id' => $users->random()->id,
            'title' => 'Post sin descripción'
        ]);

        // Crear varios posts para un usuario específico (útil para testing)
        $testUser = User::first();
        Post::factory()->count(5)->create([
            'user_id' => $testUser->id,
            'title' => 'Post de prueba del usuario ' . $testUser->name . ' 🧪'
        ]);
    }
}
