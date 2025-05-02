<?php

namespace Tests\Feature\Comment\V1;

use App\Models\V1\Comment;
use App\Models\V1\Image;
use App\Models\V1\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CommentDeleteOneImageTest extends TestCase
{
    use RefreshDatabase;
    private $user;
    private $comment;
    private $image;
    private $encryptedCommentId;
    private $encryptedImageId;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->user = User::factory()->create();
        $this->comment = Comment::factory()->create(['user_id' => $this->user->id]);

        // Crear imagen asociada al comentario
        $this->image = Image::factory()->create([
            'imageable_id' => $this->comment->id,
            'imageable_type' => Comment::class,
            'image_Uuid' => 'comments/images/image1.jpg'
        ]);

        Storage::disk('public')->put($this->image->image_Uuid, 'dummy');

        // Encriptar IDs
        $this->encryptedCommentId = Crypt::encrypt($this->comment->id);
        $this->encryptedImageId = Crypt::encrypt($this->image->id);
    }

    /**
     *
     * Caso exitoso eliminar una imagen
     */
    public function test_successfully_delete_single_image()
    {
        $this->actingAs($this->user);

        $response = $this->deleteJson("/api/v1/comments/{$this->encryptedCommentId}/images/{$this->encryptedImageId}");

        $response->assertStatus(200);

        // Verificar eliminación de la imagen
        Storage::disk('public')->assertMissing($this->image->image_Uuid);
        $this->assertDatabaseMissing('images', ['id' => $this->image->id]);

        // Verificar que el comentario sigue existiendo
        $this->assertDatabaseHas('comments', ['id' => $this->comment->id]);
    }

    /**
     *
     * Caso erróneo no autorizado
     */
    public function test_fail_delete_image_unauthorized()
    {
        $otherUser = User::factory()->create();
        $this->actingAs($otherUser);

        $response = $this->deleteJson("/api/v1/comments/{$this->encryptedCommentId}/images/{$this->encryptedImageId}");

        $response->assertStatus(403);

        // Verificar que la imagen no se eliminó
        Storage::disk('public')->assertExists($this->image->image_Uuid);
        $this->assertDatabaseHas('images', ['id' => $this->image->id]);
    }

    /**
     *
     * Caso erróneo comentario no encontrado
     */
    public function test_fail_delete_image_comment_not_found()
    {
        $this->actingAs($this->user);

        $nonExistentCommentId = Crypt::encrypt(9999);
        $response = $this->deleteJson("/api/v1/comments/{$nonExistentCommentId}/images/{$this->encryptedImageId}");

        $response->assertStatus(404);
    }

    /**
     *
     * Caso erróneo imagen no encontrada
     */
    public function test_fail_delete_image_not_found()
    {
        $this->actingAs($this->user);

        $nonExistentImageId = Crypt::encrypt(9999);
        $response = $this->deleteJson("/api/v1/comments/{$this->encryptedCommentId}/images/{$nonExistentImageId}");

        $response->assertStatus(404);
    }

    /**
     *
     * Caso erróneo comentario id alterado
     */
    public function test_fail_delete_image_with_tampered_comment_id()
    {
        $this->actingAs($this->user);

        $tamperedCommentId = substr($this->encryptedCommentId, 0, -5) . 'abcde';
        $response = $this->deleteJson("/api/v1/comments/{$tamperedCommentId}/images/{$this->encryptedImageId}");

        $response->assertStatus(500);
    }

    /**
     *
     * Caso erróneo imagen id alterado
     */
    public function test_fail_delete_image_with_tampered_image_id()
    {
        $this->actingAs($this->user);

        $tamperedImageId = substr($this->encryptedImageId, 0, -5) . 'abcde';
        $response = $this->deleteJson("/api/v1/comments/{$this->encryptedCommentId}/images/{$tamperedImageId}");

        $response->assertStatus(500);
    }

    /**
     *
     * Caso adicional: Verificar que solo se elimina la imagen especificada
     */
    public function test_only_specified_image_is_deleted()
    {
        $this->actingAs($this->user);

        // Crear segunda imagen
        $secondImage = Image::factory()->create([
            'imageable_id' => $this->comment->id,
            'imageable_type' => Comment::class,
            'image_Uuid' => 'comments/images/image2.jpg'
        ]);
        $secondImageEncryptedId = Crypt::encrypt($secondImage->id);
        Storage::disk('public')->put($secondImage->image_Uuid, 'dummy');

        $response = $this->deleteJson("/api/v1/comments/{$this->encryptedCommentId}/images/{$this->encryptedImageId}");

        $response->assertStatus(200);

        // Verificar que solo se eliminó la imagen especificada
        Storage::disk('public')->assertMissing($this->image->image_Uuid);
        Storage::disk('public')->assertExists($secondImage->image_Uuid);

        $this->assertDatabaseMissing('images', ['id' => $this->image->id]);
        $this->assertDatabaseHas('images', ['id' => $secondImage->id]);
    }
}
