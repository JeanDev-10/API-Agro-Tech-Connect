<?php

namespace Tests\Feature\Valorations\V1;

use App\Models\V1\Reaction;
use App\Models\V1\ReplayComment;
use App\Models\V1\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class ReplayCommentReactionTest extends TestCase
{
    use RefreshDatabase;

    private $user;
    private $replayComment;
    private $encryptedReplayCommentId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->replayComment = ReplayComment::factory()->create();
        $this->encryptedReplayCommentId = Crypt::encrypt($this->replayComment->id);
    }


    public function test_can_add_reaction_to_post_without_previous_reaction()
    {
        $this->actingAs($this->user)
            ->postJson("/api/v1/replaycomments/{$this->encryptedReplayCommentId}/reactions", [
                'type' => 'positivo'
            ])
            ->assertStatus(201);

        $this->assertDatabaseHas('reactions', [
            'user_id' => $this->user->id,
            'reactionable_id' => $this->replayComment->id,
            'reactionable_type' => ReplayComment::class,
            'type' => 'positivo'
        ]);
    }


    public function test_can_change_reaction_from_negative_to_positive()
    {
        Reaction::factory()->create([
            'user_id' => $this->user->id,
            'reactionable_id' => $this->replayComment->id,
            'reactionable_type' => ReplayComment::class,
            'type' => 'negativo'
        ]);

        $this->actingAs($this->user)
            ->postJson("/api/v1/replaycomments/{$this->encryptedReplayCommentId}/reactions", [
                'type' => 'positivo'
            ])
            ->assertStatus(201);

        $this->assertDatabaseHas('reactions', [
            'user_id' => $this->user->id,
            'reactionable_id' => $this->replayComment->id,
            'type' => 'positivo'
        ]);
    }


    public function test_can_change_reaction_from_positive_to_negative()
    {
        Reaction::factory()->create([
            'user_id' => $this->user->id,
            'reactionable_id' => $this->replayComment->id,
            'reactionable_type' => ReplayComment::class,
            'type' => 'positivo'
        ]);

        $this->actingAs($this->user)
            ->postJson("/api/v1/replaycomments/{$this->encryptedReplayCommentId}/reactions", [
                'type' => 'negativo'
            ])
            ->assertStatus(201);

        $this->assertDatabaseHas('reactions', [
            'user_id' => $this->user->id,
            'reactionable_id' => $this->replayComment->id,
            'type' => 'negativo'
        ]);
    }


    public function test_cannot_add_same_positive_reaction_twice()
    {
        Reaction::factory()->create([
            'user_id' => $this->user->id,
            'reactionable_id' => $this->replayComment->id,
            'reactionable_type' => ReplayComment::class,
            'type' => 'positivo'
        ]);

        $this->actingAs($this->user)
            ->postJson("/api/v1/replaycomments/{$this->encryptedReplayCommentId}/reactions", [
                'type' => 'positivo'
            ])
            ->assertStatus(400)
            ->assertJson([
                'error' => true,
                'message' => 'Ya has reaccionado con este tipo anteriormente'
            ]);
    }


    public function test_cannot_add_same_negative_reaction_twice()
    {
        Reaction::factory()->create([
            'user_id' => $this->user->id,
            'reactionable_id' => $this->replayComment->id,
            'reactionable_type' => ReplayComment::class,
            'type' => 'negativo'
        ]);

        $this->actingAs($this->user)
            ->postJson("/api/v1/replaycomments/{$this->encryptedReplayCommentId}/reactions", [
                'type' => 'negativo'
            ])
            ->assertStatus(400)
            ->assertJson([
                'error' => true,
                'message' => 'Ya has reaccionado con este tipo anteriormente'
            ]);
    }


    public function test_replaycomment_not_found_returns_404()
    {
        $nonExistentId = Crypt::encrypt(999999);

        $this->actingAs($this->user)
            ->postJson("/api/v1/replaycomments/{$nonExistentId}/reactions", [
                'type' => 'positivo'
            ])
            ->assertStatus(404)
            ->assertJson([
                'error' => true,
                'message' => 'Respuesta no encontrado'
            ]);
    }


    public function test_tampered_id_returns_400()
    {
        $tamperedId = substr($this->encryptedReplayCommentId, 0, -5) . 'abcde';

        $this->actingAs($this->user)
            ->postJson("/api/v1/replaycomments/{$tamperedId}/reactions", [
                'type' => 'positivo'
            ])
            ->assertStatus(500);
    }


    public function test_type_field_is_required()
    {
        $this->actingAs($this->user)
            ->postJson("/api/v1/replaycomments/{$this->encryptedReplayCommentId}/reactions", [])
            ->assertStatus(422)
            ->assertJson([
                'message' => 'Error de validación',
                'data' => [
                    'type' => [
                        'El tipo de reacción es obligatorio.'
                    ]
                ]
            ]);
    }


    public function test_type_field_only_accepts_valid_values()
    {
        $this->actingAs($this->user)
            ->postJson("/api/v1/replaycomments/{$this->encryptedReplayCommentId}/reactions", [
                'type' => 'invalido'
            ])
            ->assertStatus(422)
            ->assertJson([
                'message' => 'Error de validación',
                'data' => [
                    'type' => [
                        'El tipo de reacción debe ser positivo o negativo.'
                    ]
                ]
            ]);
    }
}
