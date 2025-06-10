<?php

namespace Database\Seeders\V1;

use App\Models\V1\Comment;
use App\Models\V1\Post;
use App\Models\V1\ReplayComment;
use App\Models\V1\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ImageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users=User::all();
        $posts=Post::all();
        $comments=Comment::all();
        $replayComments=ReplayComment::all();

        foreach ($users as $user) {
            $user->image()->create([
                'url' => "https://i.ibb.co/rKCScRx8/perfil1.png",
                'image_uuid' => \Illuminate\Support\Str::uuid(),
            ]);
        }
        foreach ($posts as $post) {
            $post->images()->create([
                'url' => $this->generateRandomAvatar($post->id),
                'image_uuid' => \Illuminate\Support\Str::uuid(),
            ]);
        }
        foreach ($comments as $comment) {
            $comment->images()->create([
                'url' => $this->generateRandomAvatar($comment->id),
                'image_uuid' => \Illuminate\Support\Str::uuid(),
            ]);
        }
        foreach ($replayComments as $replayComment) {
            $replayComment->images()->create([
                'url' => $this->generateRandomAvatar($replayComment->id),
                'image_uuid' => \Illuminate\Support\Str::uuid(),
            ]);
        }
    }
    protected function generateRandomAvatar(string $seed): string
    {
        $styles = ['adventurer', 'avataaars', 'big-ears', 'bottts', 'croodles'];
        $style = $styles[rand(0, count($styles) - 1)];

        return "https://avatars.dicebear.com/api/{$style}/{$seed}.svg";
    }
}
