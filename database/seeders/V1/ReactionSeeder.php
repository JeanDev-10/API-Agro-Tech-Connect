<?php

namespace Database\Seeders\V1;

use App\Models\V1\Comment;
use App\Models\V1\Post;
use App\Models\V1\Reaction;
use App\Models\V1\ReplayComment;
use App\Models\V1\User;
use Illuminate\Database\Seeder;

class ReactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Verificar si hay elementos para reaccionar
        if (Post::count() === 0) {
            Post::factory()->count(10)->create();
        }
        if (Comment::count() === 0) {
            Comment::factory()->count(20)->create();
        }
        if (ReplayComment::count() === 0) {
            ReplayComment::factory()->count(30)->create();
        }
        if (User::count() < 5) {
            User::factory()->count(5)->create();
        }

        // Obtener todos los elementos reactivos
        $posts = Post::all();
        $comments = Comment::all();
        $replies = ReplayComment::all();
        $users = User::all();

        // Crear reacciones para posts (1-3 reacciones por post)
        $posts->each(function ($post) use ($users) {
            $reactors = $users->where('id', '!=', $post->user_id)
                            ->random(rand(1, 3));

            foreach ($reactors as $user) {
                Reaction::factory()
                    ->create([
                        'user_id' => $user->id,
                        'reactionable_id' => $post->id,
                        'reactionable_type' => Post::class
                    ]);
            }
        });

        // Crear reacciones para comentarios (1-2 reacciones por comentario)
        $comments->each(function ($comment) use ($users) {
            $reactors = $users->where('id', '!=', $comment->user_id)
                            ->random(rand(1, 2));

            foreach ($reactors as $user) {
                Reaction::factory()
                    ->create([
                        'user_id' => $user->id,
                        'reactionable_id' => $comment->id,
                        'reactionable_type' => Comment::class
                    ]);
            }
        });

        // Crear algunas reacciones para respuestas (aleatorias)
        $replies->random(15)->each(function ($reply) use ($users) {
            $user = $users->where('id', '!=', $reply->user_id)
                        ->random();

            Reaction::factory()
                ->create([
                    'user_id' => $user->id,
                    'reactionable_id' => $reply->id,
                    'reactionable_type' => ReplayComment::class
                ]);
        });

        // Crear algunos casos especiales
        $firstPost = Post::oldest()->first();
        Reaction::factory()
            ->positive()
            ->sameUser()
            ->create([
                'reactionable_id' => $firstPost->id,
                'reactionable_type' => Post::class
            ]);

        $controversialComment = Comment::inRandomOrder()->first();
        Reaction::factory()
            ->positive()
            ->create([
                'reactionable_id' => $controversialComment->id,
                'reactionable_type' => Comment::class
            ]);
        Reaction::factory()
            ->negative()
            ->create([
                'reactionable_id' => $controversialComment->id,
                'reactionable_type' => Comment::class
            ]);
    }
}
