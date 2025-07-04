<?php

namespace Tests\Feature\Valorations\V1\Ranges;

use App\Events\V1\CommentReactionEvent;
use App\Listeners\V1\CommentReactionListener;
use App\Models\V1\Comment;
use App\Models\V1\Range;
use App\Models\V1\Reaction;
use App\Models\V1\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentReactionListenerTest extends TestCase
{
    use RefreshDatabase;

    private $listener;
    private $user;
    private $comment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\V1\RangeSeeder::class);
        $this->listener = new CommentReactionListener();
        $this->user = User::factory()->create();
        $this->comment = Comment::factory()->create(['user_id' => $this->user->id]);
    }


    public function test_assigns_iniciado_range_on_first_positive_reaction()
    {
        $reaction = Reaction::factory()->create([
            'reactionable_id' => $this->comment->id,
            'reactionable_type' => Comment::class,
            'type' => 'positivo'
        ]);

        $event = new CommentReactionEvent($this->comment, $reaction);
        $this->listener->handle($event);

        $this->user->refresh();
        $this->assertEquals('Iniciado', $this->user->ranges->first()->name);
    }


    public function test_does_not_assign_duplicate_ranges()
    {
        $iniciado = Range::where('name', 'Iniciado')->first();
        $this->user->ranges()->attach($iniciado);

        $reaction = Reaction::factory()->create([
            'reactionable_id' => $this->comment->id,
            'reactionable_type' => Comment::class,
            'type' => 'positivo'
        ]);

        $event = new CommentReactionEvent($this->comment, $reaction);
        $this->listener->handle($event);

        $this->user->refresh();
        $this->assertCount(1, $this->user->ranges);
    }
}
