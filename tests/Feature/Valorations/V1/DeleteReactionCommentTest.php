<?php

namespace Tests\Feature\Valorations\V1;

use App\Models\V1\Comment;
use App\Models\V1\Reaction;
use App\Models\V1\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DeleteReactionCommentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }


    public function test_user_can_delete_their_reaction_to_comment_successfully()
    {
        // Crear usuarios y comentario
        $user = User::factory()->create();
        $comment = Comment::factory()->create();

        // Crear reacción
        Reaction::factory()->create([
            'user_id' => $user->id,
            'reactionable_id' => $comment->id,
            'reactionable_type' => Comment::class
        ]);

        // Encriptar ID
        $encryptedId = Crypt::encrypt($comment->id);

        // Hacer la petición
        $response = $this->actingAs($user)
            ->deleteJson("/api/v1/comments/{$encryptedId}/reactions");

        // Verificaciones
        $response->assertStatus(204);

        // Verificar que la reacción fue eliminada
        $this->assertDatabaseMissing('reactions', [
            'user_id' => $user->id,
            'reactionable_id' => $comment->id,
            'reactionable_type' => Comment::class
        ]);
    }


    public function test_cannot_delete_reaction_to_comment_when_not_reacted()
    {
        $user = User::factory()->create();
        $comment = Comment::factory()->create();

        $encryptedId = Crypt::encrypt($comment->id);

        $response = $this->actingAs($user)
            ->deleteJson("/api/v1/comments/{$encryptedId}/reactions");

        $response->assertStatus(400)
            ->assertJson(['message' => 'Aún no has reaccionado a este comentario']);
    }


    public function test_cannot_delete_reaction_for_nonexistent_comment()
    {
        $user = User::factory()->create();

        // ID que no existe
        $nonExistentId = 9999;
        $encryptedId = Crypt::encrypt($nonExistentId);

        $response = $this->actingAs($user)
            ->deleteJson("/api/v1/comments/{$encryptedId}/reactions");

        $response->assertStatus(404)
            ->assertJson(['message' => 'Comentario no encontrado']);
    }


    public function test_cannot_delete_comment_reaction_with_tampered_id()
    {
        $user = User::factory()->create();
        $comment = Comment::factory()->create();

        // ID alterado (añadiendo caracteres al ID cifrado)
        $encryptedId = Crypt::encrypt($comment->id) . 'tampered';

        $response = $this->actingAs($user)
            ->deleteJson("/api/v1/comments/{$encryptedId}/reactions");

        $response->assertStatus(500)
            ->assertJson(['message' => 'Error al eliminar reacción: The payload is invalid.']);
    }


    public function test_unauthenticated_user_cannot_delete_comment_reaction()
    {
        $comment = Comment::factory()->create();
        $encryptedId = Crypt::encrypt($comment->id);

        $response = $this->deleteJson("/api/v1/comments/{$encryptedId}/reactions");

        $response->assertStatus(401);
    }
}
