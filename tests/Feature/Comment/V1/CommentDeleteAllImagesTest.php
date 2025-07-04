<?php

namespace Tests\Feature\Comment\V1;

use App\Models\V1\Comment;
use App\Models\V1\Image;
use App\Models\V1\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CommentDeleteAllImagesTest extends TestCase
{
    private $user;
    private $comment;
    private $encryptedId;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->user = User::factory()->create();
        $this->comment = Comment::factory()->create(['user_id' => $this->user->id]);
        $this->encryptedId = Crypt::encrypt($this->comment->id);
    }

    /**
     * Caso exitoso: Eliminar todas las imágenes del comentario
     */
    public function test_successfully_delete_comment_images()
    {
        $this->actingAs($this->user);

        // Crear imágenes asociadas al comentario
        $image1 = Image::factory()->create([
            'imageable_id' => $this->comment->id,
            'imageable_type' => Comment::class,
            'image_Uuid' => 'comments/images/image1.jpg'
        ]);

        $image2 = Image::factory()->create([
            'imageable_id' => $this->comment->id,
            'imageable_type' => Comment::class,
            'image_Uuid' => 'comments/images/image2.jpg'
        ]);

        // Crear archivos falsos en el storage
        Storage::disk('public')->put($image1->image_Uuid, 'dummy');
        Storage::disk('public')->put($image2->image_Uuid, 'dummy');

        $response = $this->deleteJson("/api/v1/comments/{$this->encryptedId}/images");

        $response->assertStatus(200);

        // Verificar eliminación de imágenes del storage
        Storage::disk('public')->assertMissing($image1->image_Uuid);
        Storage::disk('public')->assertMissing($image2->image_Uuid);

        // Verificar eliminación de registros en BD
        $this->assertDatabaseMissing('images', ['id' => $image1->id]);
        $this->assertDatabaseMissing('images', ['id' => $image2->id]);

        // Verificar que el comentario sigue existiendo
        $this->assertDatabaseHas('comments', ['id' => $this->comment->id]);
    }

    /**
     * Caso exitoso: Eliminar imágenes cuando el comentario no tiene imágenes
     */
    public function test_successfully_delete_images_from_comment_without_images()
    {
        $this->actingAs($this->user);

        $response = $this->deleteJson("/api/v1/comments/{$this->encryptedId}/images");

        $response->assertStatus(200);
    }

    /**
     * Caso erróneo: Eliminar imágenes de un comentario que no me pertenece
     */
    public function test_fail_delete_images_from_another_users_comment()
    {
        $otherUser = User::factory()->create();
        $this->actingAs($otherUser);

        $response = $this->deleteJson("/api/v1/comments/{$this->encryptedId}/images");

        $response->assertStatus(403);
    }

    /**
     * Caso erróneo: Comentario no encontrado
     */
    public function test_fail_delete_images_from_non_existent_comment()
    {
        $this->actingAs($this->user);
        $nonExistentId = Crypt::encrypt(999999);

        $response = $this->deleteJson("/api/v1/comments/{$nonExistentId}/images");

        $response->assertStatus(404);
    }

    /**
     * Caso erróneo: ID alterado
     */
    public function test_fail_delete_images_with_tampered_id()
    {
        $this->actingAs($this->user);
        $tamperedId = substr($this->encryptedId, 0, -5) . 'abcde'; // Alterar el ID

        $response = $this->deleteJson("/api/v1/comments/{$tamperedId}/images");

        $response->assertStatus(500);
    }

    /**
     * Caso adicional: Verificar que solo se eliminan imágenes del comentario
     */
    public function test_only_comment_images_are_deleted()
    {
        $this->actingAs($this->user);

        // Imagen del comentario
        $commentImage = Image::factory()->create([
            'imageable_id' => $this->comment->id,
            'imageable_type' => Comment::class,
            'image_Uuid' => 'comments/images/comment1.jpg'
        ]);

        // Imagen de otro modelo (no debe eliminarse)
        $otherImage = Image::factory()->create([
            'image_Uuid' => 'users/images/user1.jpg'
        ]);

        Storage::disk('public')->put($commentImage->image_Uuid, 'dummy');
        Storage::disk('public')->put($otherImage->image_Uuid, 'dummy');

        $response = $this->deleteJson("/api/v1/comments/{$this->encryptedId}/images");

        $response->assertStatus(200);

        // Verificar que solo se eliminó la imagen del comentario
        Storage::disk('public')->assertMissing($commentImage->image_Uuid);
        Storage::disk('public')->assertExists($otherImage->image_Uuid);

        $this->assertDatabaseMissing('images', ['id' => $commentImage->id]);
        $this->assertDatabaseHas('images', ['id' => $otherImage->id]);
    }
}
