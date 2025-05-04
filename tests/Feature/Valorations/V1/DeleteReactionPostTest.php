<?php

namespace Tests\Feature\Valorations\V1;

use App\Models\V1\Post;
use App\Models\V1\Reaction;
use App\Models\V1\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DeleteReactionPostTest extends TestCase
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


    public function test_user_can_delete_their_reaction_successfully()
    {
        // Crear usuarios y post
        $user = User::factory()->create();
        $post = Post::factory()->create();

        // Crear reacción
        Reaction::factory()->create([
            'user_id' => $user->id,
            'reactionable_id' => $post->id,
            'reactionable_type' => Post::class
        ]);

        // Encriptar ID
        $encryptedId = Crypt::encrypt($post->id);

        // Hacer la petición
        $response = $this->actingAs($user)
            ->deleteJson("/api/v1/posts/{$encryptedId}/reactions");

        // Verificaciones
        $response->assertStatus(204);

        // Verificar que la reacción fue eliminada
        $this->assertDatabaseMissing('reactions', [
            'user_id' => $user->id,
            'reactionable_id' => $post->id,
            'reactionable_type' => Post::class
        ]);
    }


    public function test_cannot_delete_reaction_when_not_reacted()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $encryptedId = Crypt::encrypt($post->id);

        $response = $this->actingAs($user)
            ->deleteJson("/api/v1/posts/{$encryptedId}/reactions");

        $response->assertStatus(400)
            ->assertJson(['message' => 'Aún no has reaccionado a esta publicación']);
    }


    public function test_cannot_delete_reaction_for_nonexistent_post()
    {
        $user = User::factory()->create();

        // ID que no existe
        $nonExistentId = 9999;
        $encryptedId = Crypt::encrypt($nonExistentId);

        $response = $this->actingAs($user)
            ->deleteJson("/api/v1/posts/{$encryptedId}/reactions");

        $response->assertStatus(404)
            ->assertJson(['message' => 'Post no encontrado']);
    }


    public function test_cannot_delete_reaction_with_tampered_id()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        // ID alterado (añadiendo caracteres al ID cifrado)
        $encryptedId = Crypt::encrypt($post->id) . 'tampered';

        $response = $this->actingAs($user)
            ->deleteJson("/api/v1/posts/{$encryptedId}/reactions");

        $response->assertStatus(500)
            ->assertJson(['message' => 'Error al eliminar reacción: The payload is invalid.']);
    }


    public function test_unauthenticated_user_cannot_delete_reaction()
    {
        $post = Post::factory()->create();
        $encryptedId = Crypt::encrypt($post->id);

        $response = $this->deleteJson("/api/v1/posts/{$encryptedId}/reactions");

        $response->assertStatus(401);
    }
}
