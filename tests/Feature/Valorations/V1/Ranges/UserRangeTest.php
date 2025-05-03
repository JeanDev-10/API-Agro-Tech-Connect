<?php

namespace Tests\Feature\V1;

use App\Events\V1\CommentReactionEvent;
use App\Models\V1\Comment;
use App\Models\V1\Range;
use App\Models\V1\Reaction;
use App\Models\V1\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRangeTest extends TestCase
{
    use RefreshDatabase;

    private $user;
    private $ranges;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\V1\RangeSeeder::class);
        $this->user = User::factory()->create();
        $this->ranges = Range::orderBy('min_range')->get();
    }


    public function test_user_gets_iniciado_range_with_first_positive_reaction()
    {
        $comment = Comment::factory()->create(['user_id' => $this->user->id]);

        // Crear reacción positiva
        $reaction = Reaction::factory()->create([
            'reactionable_id' => $comment->id,
            'reactionable_type' => Comment::class,
            'type' => 'positivo',
            'user_id' => User::factory()->create()->id // Otro usuario reacciona
        ]);

        // Disparar evento manualmente para el test
        event(new CommentReactionEvent($comment, $reaction));

        $this->user->refresh();
        $this->assertCount(1, $this->user->ranges);
        $this->assertEquals('Iniciado', $this->user->ranges->first()->name);
    }


    public function test_user_advances_to_novato_at_50_positives()
    {
        // Crear 50 comentarios con 1 reacción positiva cada uno
        $comments = Comment::factory()->count(50)->create(['user_id' => $this->user->id]);
        $reactor = User::factory()->create();

        foreach ($comments as $comment) {
            $reaction = Reaction::factory()->create([
                'reactionable_id' => $comment->id,
                'reactionable_type' => Comment::class,
                'type' => 'positivo',
                'user_id' => $reactor->id
            ]);

            event(new CommentReactionEvent($comment, $reaction));
        }

        $this->user->refresh();
        $highestRange = $this->user->ranges()->orderByDesc('min_range')->first();
        $this->assertEquals('Novato', $highestRange->name);
        $this->assertCount(2, $this->user->ranges);
    }


    public function test_user_cannot_lose_achieved_ranges()
    {
        // Alcanzar rango Novato (50+ positivos)
        $comments = Comment::factory()->count(60)->create(['user_id' => $this->user->id]);
        $reactor = User::factory()->create();

        foreach ($comments as $comment) {
            $reaction = Reaction::factory()->create([
                'reactionable_id' => $comment->id,
                'reactionable_type' => Comment::class,
                'type' => 'positivo',
                'user_id' => $reactor->id
            ]);

            event(new CommentReactionEvent($comment, $reaction));
        }

        // Eliminar algunas reacciones (quedan 40 positivos)
        $this->user->comments()->limit(20)->get()->each(function ($comment) {
            $comment->positiveReactions()->delete();
        });

        $this->user->refresh();
        $this->assertCount(2, $this->user->ranges);
    }


    public function test_negative_reactions_do_not_count_for_ranges()
    {
        $comment = Comment::factory()->create(['user_id' => $this->user->id]);

        // Crear reacción negativa
        $reaction = Reaction::factory()->create([
            'reactionable_id' => $comment->id,
            'reactionable_type' => Comment::class,
            'type' => 'negativo',
            'user_id' => User::factory()->create()->id
        ]);

        event(new CommentReactionEvent($comment, $reaction));

        $this->user->refresh();
        $this->assertCount(0, $this->user->ranges);
    }


    public function test_user_reaches_gran_maestro_range()
    {
        // Configuración inicial
        $this->user->ranges()->attach(Range::where('name', 'Gran Maestro')->first());

        // Simular que ya tiene 24,999 reacciones positivas
        $positiveCount = 24999;
        $this->user->update(['positive_reactions_count' => $positiveCount]);

        // Crear un comentario y una reacción que lleve al usuario a 25,000
        $comment = Comment::factory()->create(['user_id' => $this->user->id]);
        $reaction = Reaction::factory()->create([
            'reactionable_id' => $comment->id,
            'reactionable_type' => Comment::class,
            'type' => 'positivo',
            'user_id' => User::factory()->create()->id
        ]);

        event(new CommentReactionEvent($comment, $reaction));

        $this->user->refresh();
        $highestRange = $this->user->ranges()->orderByDesc('min_range')->first();
        $this->assertEquals('Gran Maestro', $highestRange->name);
    }
    public function test_user_reaches_leyenda_range()
    {
        // Configuración inicial
        $this->user->ranges()->attach(Range::where('name', 'Gran Maestro')->first());

        // Simular que ya tiene 24,999 reacciones positivas
        $positiveCount = 25001;
        $this->user->update(['positive_reactions_count' => $positiveCount]);

        // Crear un comentario y una reacción que lleve al usuario a 25,000
        $comment = Comment::factory()->create(['user_id' => $this->user->id]);
        $reaction = Reaction::factory()->create([
            'reactionable_id' => $comment->id,
            'reactionable_type' => Comment::class,
            'type' => 'positivo',
            'user_id' => User::factory()->create()->id
        ]);

        event(new CommentReactionEvent($comment, $reaction));

        $this->user->refresh();
        $highestRange = $this->user->ranges()->orderByDesc('min_range')->first();
        $this->assertEquals('Gran Maestro', $highestRange->name);
    }
    public function test_user_reaches_leyenda_max_range()
    {
        // Configuración inicial
        $this->user->ranges()->attach(Range::where('name', 'Gran Maestro')->first());

        // Simular que ya tiene 24,999 reacciones positivas
        $positiveCount = 27001;
        $this->user->update(['positive_reactions_count' => $positiveCount]);

        // Crear un comentario y una reacción que lleve al usuario a 25,000
        $comment = Comment::factory()->create(['user_id' => $this->user->id]);
        $reaction = Reaction::factory()->create([
            'reactionable_id' => $comment->id,
            'reactionable_type' => Comment::class,
            'type' => 'positivo',
            'user_id' => User::factory()->create()->id
        ]);

        event(new CommentReactionEvent($comment, $reaction));

        $this->user->refresh();
        $highestRange = $this->user->ranges()->orderByDesc('min_range')->first();
        $this->assertEquals('Gran Maestro', $highestRange->name);
    }
}
