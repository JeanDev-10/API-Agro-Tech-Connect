<?php

namespace Tests\Feature\V1\Valorations;

use App\Models\V1\Comment;
use App\Models\V1\Reaction;
use App\Models\V1\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class CommentReactionTest extends TestCase
{
    use RefreshDatabase;

    private $user;
    private $comment;
    private $encryptedCommentId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->comment = Comment::factory()->create();
        $this->encryptedCommentId = Crypt::encrypt($this->comment->id);
    }


    public function test_can_add_reaction_to_comment_without_previous_reaction()
    {
        $this->actingAs($this->user)
            ->postJson("/api/v1/comments/{$this->encryptedCommentId}/reactions", [
                'type' => 'positivo'
            ])
            ->assertStatus(201);

        $this->assertDatabaseHas('reactions', [
            'user_id' => $this->user->id,
            'reactionable_id' => $this->comment->id,
            'reactionable_type' => Comment::class,
            'type' => 'positivo'
        ]);
    }


    public function test_can_change_reaction_from_negative_to_positive()
    {
        Reaction::factory()->create([
            'user_id' => $this->user->id,
            'reactionable_id' => $this->comment->id,
            'reactionable_type' => Comment::class,
            'type' => 'negativo'
        ]);

        $this->actingAs($this->user)
            ->postJson("/api/v1/comments/{$this->encryptedCommentId}/reactions", [
                'type' => 'positivo'
            ])
            ->assertStatus(201);

        $this->assertDatabaseHas('reactions', [
            'user_id' => $this->user->id,
            'reactionable_id' => $this->comment->id,
            'type' => 'positivo'
        ]);
    }


    public function test_can_change_reaction_from_positive_to_negative()
    {
        Reaction::factory()->create([
            'user_id' => $this->user->id,
            'reactionable_id' => $this->comment->id,
            'reactionable_type' => Comment::class,
            'type' => 'positivo'
        ]);

        $this->actingAs($this->user)
            ->postJson("/api/v1/comments/{$this->encryptedCommentId}/reactions", [
                'type' => 'negativo'
            ])
            ->assertStatus(201);

        $this->assertDatabaseHas('reactions', [
            'user_id' => $this->user->id,
            'reactionable_id' => $this->comment->id,
            'type' => 'negativo'
        ]);
    }


    public function test_cannot_add_same_positive_reaction_twice()
    {
        Reaction::factory()->create([
            'user_id' => $this->user->id,
            'reactionable_id' => $this->comment->id,
            'reactionable_type' => Comment::class,
            'type' => 'positivo'
        ]);

        $this->actingAs($this->user)
            ->postJson("/api/v1/comments/{$this->encryptedCommentId}/reactions", [
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
            'reactionable_id' => $this->comment->id,
            'reactionable_type' => Comment::class,
            'type' => 'negativo'
        ]);

        $this->actingAs($this->user)
            ->postJson("/api/v1/comments/{$this->encryptedCommentId}/reactions", [
                'type' => 'negativo'
            ])
            ->assertStatus(400)
            ->assertJson([
                'error' => true,
                'message' => 'Ya has reaccionado con este tipo anteriormente'
            ]);
    }


    public function test_comment_not_found_returns_404()
    {
        $nonExistentId = Crypt::encrypt(999999);

        $this->actingAs($this->user)
            ->postJson("/api/v1/comments/{$nonExistentId}/reactions", [
                'type' => 'positivo'
            ])
            ->assertStatus(404)
            ->assertJson([
                'error' => true,
                'message' => 'Comentario no encontrado'
            ]);
    }


    public function test_tampered_id_returns_400()
    {
        $tamperedId = substr($this->encryptedCommentId, 0, -5) . 'abcde';

        $this->actingAs($this->user)
            ->postJson("/api/v1/comments/{$tamperedId}/reactions", [
                'type' => 'positivo'
            ])
            ->assertStatus(500);
    }


    public function test_type_field_is_required()
    {
        $this->actingAs($this->user)
            ->postJson("/api/v1/comments/{$this->encryptedCommentId}/reactions", [])
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
            ->postJson("/api/v1/comments/{$this->encryptedCommentId}/reactions", [
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
