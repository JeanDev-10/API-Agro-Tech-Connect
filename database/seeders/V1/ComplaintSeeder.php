<?php

namespace Database\Seeders\V1;

use App\Models\V1\Complaint;
use App\Models\V1\Post;
use App\Models\V1\Comment;
use App\Models\V1\ReplayComment;
use App\Models\V1\User;
use Illuminate\Database\Seeder;

class ComplaintSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();
        $posts = Post::all();
        $comments = Comment::all();
        $replies = ReplayComment::all();

        // Crear denuncias para posts
        foreach ($posts->take(5) as $post) {
            Complaint::create([
                'description' => 'Contenido inapropiado en esta publicación',
                'complaintable_id' => $post->id,
                'complaintable_type' => Post::class,
                'user_id' => $users->random()->id
            ]);
        }

        // Crear denuncias para comentarios
        foreach ($comments->take(5) as $comment) {
            Complaint::create([
                'description' => 'Comentario ofensivo o spam',
                'complaintable_id' => $comment->id,
                'complaintable_type' => Comment::class,
                'user_id' => $users->random()->id
            ]);
        }

        // Crear denuncias para respuestas
        foreach ($replies->take(5) as $reply) {
            Complaint::create([
                'description' => 'Respuesta con información falsa',
                'complaintable_id' => $reply->id,
                'complaintable_type' => ReplayComment::class,
                'user_id' => $users->random()->id
            ]);
        }
    }
}
