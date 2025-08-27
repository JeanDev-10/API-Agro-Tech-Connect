<?php

namespace Database\Seeders\V1;

use App\Models\V1\Comment;
use App\Models\V1\ReplayComment;
use Illuminate\Database\Seeder;

class ReplayCommentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Verificar si hay comentarios
        if (Comment::count() === 0) {
            $this->call(CommentSeeder::class);
        }

        // Obtener todos los comentarios
        $comments = Comment::all();

        // Crear 2-3 respuestas por cada comentario
        $comments->each(function ($comment) {
            $replyCount = rand(2, 3);

            ReplayComment::factory()
                ->count($replyCount)
                ->create(['comment_id' => $comment->id]);
        });

        // Crear algunos casos especiales
        $firstComment = Comment::oldest()->first();

        // Respuesta larga al primer comentario
        ReplayComment::factory()
            ->longReply()
            ->create(['comment_id' => $firstComment->id]);

        // Discusión (mismo usuario respondiéndose)
        ReplayComment::factory()
            ->sameUser()
            ->create(['comment_id' => $firstComment->id]);
    }
}
