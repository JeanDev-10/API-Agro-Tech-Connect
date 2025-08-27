<?php

namespace Tests\Feature\Valorations\V1;

use App\Models\V1\Post;
use App\Models\V1\Reaction;
use App\Models\V1\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class PostReactionTest extends TestCase
{
    use RefreshDatabase;

    private $user;
    private $post;
    private $encryptedPostId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->post = Post::factory()->create();
        $this->encryptedPostId = Crypt::encrypt($this->post->id);
    }


    public function test_can_add_reaction_to_post_without_previous_reaction()
    {
        $this->actingAs($this->user)
            ->postJson("/api/v1/posts/{$this->encryptedPostId}/reactions", [
                'type' => 'positivo'
            ])
            ->assertStatus(201);

        $this->assertDatabaseHas('reactions', [
            'user_id' => $this->user->id,
            'reactionable_id' => $this->post->id,
            'reactionable_type' => Post::class,
            'type' => 'positivo'
        ]);
    }


    public function test_can_change_reaction_from_negative_to_positive()
    {
        Reaction::factory()->create([
            'user_id' => $this->user->id,
            'reactionable_id' => $this->post->id,
            'reactionable_type' => Post::class,
            'type' => 'negativo'
        ]);

        $this->actingAs($this->user)
            ->postJson("/api/v1/posts/{$this->encryptedPostId}/reactions", [
                'type' => 'positivo'
            ])
            ->assertStatus(201);

        $this->assertDatabaseHas('reactions', [
            'user_id' => $this->user->id,
            'reactionable_id' => $this->post->id,
            'type' => 'positivo'
        ]);
    }


    public function test_can_change_reaction_from_positive_to_negative()
    {
        Reaction::factory()->create([
            'user_id' => $this->user->id,
            'reactionable_id' => $this->post->id,
            'reactionable_type' => Post::class,
            'type' => 'positivo'
        ]);

        $this->actingAs($this->user)
            ->postJson("/api/v1/posts/{$this->encryptedPostId}/reactions", [
                'type' => 'negativo'
            ])
            ->assertStatus(201);

        $this->assertDatabaseHas('reactions', [
            'user_id' => $this->user->id,
            'reactionable_id' => $this->post->id,
            'type' => 'negativo'
        ]);
    }


    public function test_cannot_add_same_positive_reaction_twice()
    {
        Reaction::factory()->create([
            'user_id' => $this->user->id,
            'reactionable_id' => $this->post->id,
            'reactionable_type' => Post::class,
            'type' => 'positivo'
        ]);

        $this->actingAs($this->user)
            ->postJson("/api/v1/posts/{$this->encryptedPostId}/reactions", [
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
            'reactionable_id' => $this->post->id,
            'reactionable_type' => Post::class,
            'type' => 'negativo'
        ]);

        $this->actingAs($this->user)
            ->postJson("/api/v1/posts/{$this->encryptedPostId}/reactions", [
                'type' => 'negativo'
            ])
            ->assertStatus(400)
            ->assertJson([
                'error' => true,
                'message' => 'Ya has reaccionado con este tipo anteriormente'
            ]);
    }


    public function test_post_not_found_returns_404()
    {
        $nonExistentId = Crypt::encrypt(999999);

        $this->actingAs($this->user)
            ->postJson("/api/v1/posts/{$nonExistentId}/reactions", [
                'type' => 'positivo'
            ])
            ->assertStatus(404)
            ->assertJson([
                'error' => true,
                'message' => 'Post no encontrado'
            ]);
    }


    public function test_tampered_id_returns_400()
    {
        $tamperedId = substr($this->encryptedPostId, 0, -5) . 'abcde';

        $this->actingAs($this->user)
            ->postJson("/api/v1/posts/{$tamperedId}/reactions", [
                'type' => 'positivo'
            ])
            ->assertStatus(500);
    }


    public function test_type_field_is_required()
    {
        $this->actingAs($this->user)
            ->postJson("/api/v1/posts/{$this->encryptedPostId}/reactions", [])
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
            ->postJson("/api/v1/posts/{$this->encryptedPostId}/reactions", [
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
