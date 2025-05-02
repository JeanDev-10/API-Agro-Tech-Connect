<?php

namespace Tests\Feature\Comment\V1;

use App\Models\V1\ReplayComment;
use App\Models\V1\Image;
use App\Models\V1\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReplayCommentDeleteOneImageTest extends TestCase
{
    use RefreshDatabase;

    private $user;
    private $replayComment;
    private $image;
    private $encryptedReplayCommentId;
    private $encryptedImageId;

    /**
     * Configuración inicial para las pruebas
     */
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->user = User::factory()->create();
        $this->replayComment = ReplayComment::factory()->create(['user_id' => $this->user->id]);

        // Crear imagen asociada al ReplayComment
        $this->image = Image::factory()->create([
            'imageable_id' => $this->replayComment->id,
            'imageable_type' => ReplayComment::class,
            'image_Uuid' => 'replay_comments/images/test-image.jpg'
        ]);

        Storage::disk('public')->put($this->image->image_Uuid, 'dummy-content');

        // Encriptar IDs para las rutas
        $this->encryptedReplayCommentId = Crypt::encrypt($this->replayComment->id);
        $this->encryptedImageId = Crypt::encrypt($this->image->id);
    }

    /*****************************************************************
     * PRUEBAS POSITIVAS (CASOS DE ÉXITO)
     *****************************************************************/

    /**
     * Prueba que un usuario puede eliminar una imagen de su ReplayComment
     */
    public function test_user_can_delete_image_from_own_replay_comment()
    {
        $this->actingAs($this->user);

        $response = $this->deleteJson(
            "/api/v1/replaycomments/{$this->encryptedReplayCommentId}/images/{$this->encryptedImageId}"
        );

        $response->assertStatus(200);

        Storage::disk('public')->assertMissing($this->image->image_Uuid);
        $this->assertDatabaseMissing('images', ['id' => $this->image->id]);
        $this->assertDatabaseHas('replay_comments', ['id' => $this->replayComment->id]);
    }

    /**
     * Prueba que solo se elimina la imagen especificada
     */
    public function test_only_specified_image_is_deleted()
    {
        $this->actingAs($this->user);

        // Crear segunda imagen en el mismo ReplayComment
        $secondImage = Image::factory()->create([
            'imageable_id' => $this->replayComment->id,
            'imageable_type' => ReplayComment::class,
            'image_Uuid' => 'replay_comments/images/second-image.jpg'
        ]);
        Storage::disk('public')->put($secondImage->image_Uuid, 'dummy-content');

        $response = $this->deleteJson(
            "/api/v1/replaycomments/{$this->encryptedReplayCommentId}/images/{$this->encryptedImageId}"
        );

        $response->assertStatus(200);

        Storage::disk('public')->assertMissing($this->image->image_Uuid);
        Storage::disk('public')->assertExists($secondImage->image_Uuid);

        $this->assertDatabaseMissing('images', ['id' => $this->image->id]);
        $this->assertDatabaseHas('images', ['id' => $secondImage->id]);
    }

    /*****************************************************************
     * PRUEBAS NEGATIVAS (CASOS DE FALLO)
     *****************************************************************/

    /**
     * Prueba que un usuario no puede eliminar imágenes de ReplayComments ajenos
     */
    public function test_user_cannot_delete_image_from_other_users_replay_comment()
    {
        $otherUser = User::factory()->create();
        $this->actingAs($otherUser);

        $response = $this->deleteJson(
            "/api/v1/replaycomments/{$this->encryptedReplayCommentId}/images/{$this->encryptedImageId}"
        );

        $response->assertStatus(403);

        Storage::disk('public')->assertExists($this->image->image_Uuid);
        $this->assertDatabaseHas('images', ['id' => $this->image->id]);
    }

    /**
     * Prueba el manejo cuando el ReplayComment no existe
     */
    public function test_fails_when_replay_comment_not_found()
    {
        $this->actingAs($this->user);

        $nonExistentId = Crypt::encrypt(999999);
        $response = $this->deleteJson(
            "/api/v1/replaycomments/{$nonExistentId}/images/{$this->encryptedImageId}"
        );

        $response->assertStatus(404);
    }

    /**
     * Prueba el manejo cuando la imagen no existe
     */
    public function test_fails_when_image_not_found()
    {
        $this->actingAs($this->user);

        $nonExistentId = Crypt::encrypt(999999);
        $response = $this->deleteJson(
            "/api/v1/replaycomments/{$this->encryptedReplayCommentId}/images/{$nonExistentId}"
        );

        $response->assertStatus(404);
    }

    /**
     * Prueba el manejo cuando el ID del ReplayComment está alterado
     */
    public function test_fails_with_tampered_replay_comment_id()
    {
        $this->actingAs($this->user);

        $tamperedId = substr($this->encryptedReplayCommentId, 0, -5) . 'xxxxx';
        $response = $this->deleteJson(
            "/api/v1/replaycomments/{$tamperedId}/images/{$this->encryptedImageId}"
        );

        $response->assertStatus(500);
    }

    /**
     * Prueba el manejo cuando el ID de la imagen está alterado
     */
    public function test_fails_with_tampered_image_id()
    {
        $this->actingAs($this->user);

        $tamperedId = substr($this->encryptedImageId, 0, -5) . 'xxxxx';
        $response = $this->deleteJson(
            "/api/v1/replaycomments/{$this->encryptedReplayCommentId}/images/{$tamperedId}"
        );

        $response->assertStatus(500);
    }
}
