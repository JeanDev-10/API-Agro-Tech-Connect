<?php

namespace Tests\Feature\Comment\V1;

use App\Models\V1\ReplayComment;
use App\Models\V1\Image;
use App\Models\V1\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReplayCommentDeleteAllImagesTest extends TestCase
{
    use RefreshDatabase;
    private $user;
    private $replayComment;
    private $encryptedId;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->user = User::factory()->create();
        $this->replayComment = ReplayComment::factory()->create(['user_id' => $this->user->id]);
        $this->encryptedId = Crypt::encrypt($this->replayComment->id);
    }

    /**
     * Caso exitoso: Eliminar todas las imágenes del ReplayComment
     */
    public function test_successfully_delete_replay_comment_images()
    {
        $this->actingAs($this->user);

        // Crear imágenes asociadas al ReplayComment
        $image1 = Image::factory()->create([
            'imageable_id' => $this->replayComment->id,
            'imageable_type' => ReplayComment::class,
            'image_Uuid' => 'replay_comments/images/image1.jpg'
        ]);

        $image2 = Image::factory()->create([
            'imageable_id' => $this->replayComment->id,
            'imageable_type' => ReplayComment::class,
            'image_Uuid' => 'replay_comments/images/image2.jpg'
        ]);

        // Crear archivos falsos en el storage
        Storage::disk('public')->put($image1->image_Uuid, 'dummy');
        Storage::disk('public')->put($image2->image_Uuid, 'dummy');

        $response = $this->deleteJson("/api/v1/replaycomments/{$this->encryptedId}/images");

        $response->assertStatus(200);

        // Verificar eliminación de imágenes del storage
        Storage::disk('public')->assertMissing($image1->image_Uuid);
        Storage::disk('public')->assertMissing($image2->image_Uuid);

        // Verificar eliminación de registros en BD
        $this->assertDatabaseMissing('images', ['id' => $image1->id]);
        $this->assertDatabaseMissing('images', ['id' => $image2->id]);

        // Verificar que el ReplayComment sigue existiendo
        $this->assertDatabaseHas('replay_comments', ['id' => $this->replayComment->id]);
    }

    /**
     * Caso exitoso: Eliminar imágenes cuando el ReplayComment no tiene imágenes
     */
    public function test_successfully_delete_images_from_replay_comment_without_images()
    {
        $this->actingAs($this->user);

        $response = $this->deleteJson("/api/v1/replaycomments/{$this->encryptedId}/images");

        $response->assertStatus(200);
    }

    /**
     * Caso erróneo: Eliminar imágenes de un ReplayComment que no me pertenece
     */
    public function test_fail_delete_images_from_another_users_replay_comment()
    {
        $otherUser = User::factory()->create();
        $this->actingAs($otherUser);

        $response = $this->deleteJson("/api/v1/replaycomments/{$this->encryptedId}/images");

        $response->assertStatus(403);
    }

    /**
     * Caso erróneo: ReplayComment no encontrado
     */
    public function test_fail_delete_images_from_non_existent_replay_comment()
    {
        $this->actingAs($this->user);
        $nonExistentId = Crypt::encrypt(999999);

        $response = $this->deleteJson("/api/v1/replaycomments/{$nonExistentId}/images");

        $response->assertStatus(404);
    }

    /**
     * Caso erróneo: ID alterado
     */
    public function test_fail_delete_images_with_tampered_id()
    {
        $this->actingAs($this->user);
        $tamperedId = substr($this->encryptedId, 0, -5) . 'abcde';

        $response = $this->deleteJson("/api/v1/replaycomments/{$tamperedId}/images");

        $response->assertStatus(500);
    }

    /**
     * Caso adicional: Verificar que solo se eliminan imágenes del ReplayComment
     */
    public function test_only_replay_comment_images_are_deleted()
    {
        $this->actingAs($this->user);

        // Imagen del ReplayComment
        $replayCommentImage = Image::factory()->create([
            'imageable_id' => $this->replayComment->id,
            'imageable_type' => ReplayComment::class,
            'image_Uuid' => 'replay_comments/images/comment1.jpg'
        ]);

        // Imagen de otro modelo (no debe eliminarse)
        $otherImage = Image::factory()->create([
            'image_Uuid' => 'users/images/user1.jpg'
        ]);

        Storage::disk('public')->put($replayCommentImage->image_Uuid, 'dummy');
        Storage::disk('public')->put($otherImage->image_Uuid, 'dummy');

        $response = $this->deleteJson("/api/v1/replaycomments/{$this->encryptedId}/images");

        $response->assertStatus(200);

        // Verificaciones
        Storage::disk('public')->assertMissing($replayCommentImage->image_Uuid);
        Storage::disk('public')->assertExists($otherImage->image_Uuid);

        $this->assertDatabaseMissing('images', ['id' => $replayCommentImage->id]);
        $this->assertDatabaseHas('images', ['id' => $otherImage->id]);
    }
}
