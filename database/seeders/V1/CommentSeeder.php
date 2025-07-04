<?php

namespace Database\Seeders\V1;

use App\Models\V1\Comment;
use App\Models\V1\Post;
use App\Models\V1\User;
use Illuminate\Database\Seeder;

class CommentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Verificar si hay posts y usuarios
        if (Post::count() === 0) {
            Post::factory()->count(10)->create();
        }

        if (User::count() === 0) {
            User::factory()->count(5)->create();
        }

        // Crear 50 comentarios normales
        Comment::factory()->count(50)->create();

        // Post con varios comentarios de un mismo usuario
        $testPost = Post::latest()->first();
        $testUser = User::latest()->first();

        Comment::factory()->count(5)->create([
            'post_id' => $testPost->id,
            'user_id' => $testUser->id,
            'comment' => 'Comentario de prueba del usuario ' . $testUser->name . ' 🧪'
        ]);

        // Crear algunos comentarios con respuestas
        Comment::factory()
            ->count(3)
            ->withReplies()
            ->create();
    }
}
