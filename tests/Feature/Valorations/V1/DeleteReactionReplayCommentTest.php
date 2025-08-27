<?php

namespace Tests\Feature\Valorations\V1;

use App\Models\V1\Reaction;
use App\Models\V1\ReplayComment;
use App\Models\V1\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DeleteReactionReplayCommentTest extends TestCase
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


    public function test_user_can_delete_their_reaction_to_reply_comment_successfully()
    {
        $user = User::factory()->create();
        $ReplayComment = ReplayComment::factory()->create();

        Reaction::factory()->create([
            'user_id' => $user->id,
            'reactionable_id' => $ReplayComment->id,
            'reactionable_type' => ReplayComment::class
        ]);

        $encryptedId = Crypt::encrypt($ReplayComment->id);

        $response = $this->actingAs($user)
            ->deleteJson("/api/v1/replaycomments/{$encryptedId}/reactions");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('reactions', [
            'user_id' => $user->id,
            'reactionable_id' => $ReplayComment->id,
            'reactionable_type' => ReplayComment::class
        ]);
    }


    public function test_cannot_delete_reaction_to_reply_comment_when_not_reacted()
    {
        $user = User::factory()->create();
        $ReplayComment = ReplayComment::factory()->create();

        $encryptedId = Crypt::encrypt($ReplayComment->id);

        $response = $this->actingAs($user)
            ->deleteJson("/api/v1/replaycomments/{$encryptedId}/reactions");

        $response->assertStatus(400)
            ->assertJson(['message' => 'Aún no has reaccionado a esta respuesta']);
    }


    public function test_cannot_delete_reaction_for_nonexistent_reply_comment()
    {
        $user = User::factory()->create();

        $nonExistentId = 9999;
        $encryptedId = Crypt::encrypt($nonExistentId);

        $response = $this->actingAs($user)
            ->deleteJson("/api/v1/replaycomments/{$encryptedId}/reactions");

        $response->assertStatus(404)
            ->assertJson(['message' => 'Respuesta no encontrada']);
    }


    public function test_cannot_delete_reply_comment_reaction_with_tampered_id()
    {
        $user = User::factory()->create();
        $ReplayComment = ReplayComment::factory()->create();

        $encryptedId = Crypt::encrypt($ReplayComment->id) . 'tampered';

        $response = $this->actingAs($user)
            ->deleteJson("/api/v1/replaycomments/{$encryptedId}/reactions");

        $response->assertStatus(500)
            ->assertJson(['message' => 'Error al eliminar reacción: The payload is invalid.']);
    }


    public function test_unauthenticated_user_cannot_delete_reply_comment_reaction()
    {
        $ReplayComment = ReplayComment::factory()->create();
        $encryptedId = Crypt::encrypt($ReplayComment->id);

        $response = $this->deleteJson("/api/v1/replaycomments/{$encryptedId}/reactions");

        $response->assertStatus(401);
    }
}
