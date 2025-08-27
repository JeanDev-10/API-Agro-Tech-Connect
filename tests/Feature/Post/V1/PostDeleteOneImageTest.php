<?php

namespace Tests\Feature\Post\V1;

use App\Models\V1\Image;
use App\Models\V1\Post;
use App\Models\V1\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PostDeleteOneImageTest extends TestCase
{
    use RefreshDatabase;
    private $user;
    private $post;
    private $image;
    private $encryptedPostId;
    private $encryptedImageId;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->user = User::factory()->create();
        $this->post = Post::factory()->create(['user_id' => $this->user->id]);

        // Crear imagen asociada al post
        $this->image = Image::factory()->create([
            'imageable_id' => $this->post->id,
            'imageable_type' => Post::class,
            'image_Uuid' => 'posts/images/image1.jpg'
        ]);

        Storage::disk('public')->put($this->image->image_Uuid, 'dummy');

        // Encriptar IDs
        $this->encryptedPostId = Crypt::encrypt($this->post->id);
        $this->encryptedImageId = Crypt::encrypt($this->image->id);
    }

    /**
     *
     * Caso exitoso eliminar una imagen
     */
    public function test_successfully_delete_single_image()
    {
        $this->actingAs($this->user);

        $response = $this->deleteJson("/api/v1/posts/{$this->encryptedPostId}/images/{$this->encryptedImageId}");

        $response->assertStatus(200);

        // Verificar eliminación de la imagen
        Storage::disk('public')->assertMissing($this->image->image_Uuid);
        $this->assertDatabaseMissing('images', ['id' => $this->image->id]);

        // Verificar que el post sigue existiendo
        $this->assertDatabaseHas('posts', ['id' => $this->post->id]);
    }

    /**
     *
     * Caso erróneo no autorizado
     */
    public function test_fail_delete_image_unauthorized()
    {
        $otherUser = User::factory()->create();
        $this->actingAs($otherUser);

        $response = $this->deleteJson("/api/v1/posts/{$this->encryptedPostId}/images/{$this->encryptedImageId}");

        $response->assertStatus(403);

        // Verificar que la imagen no se eliminó
        Storage::disk('public')->assertExists($this->image->image_Uuid);
        $this->assertDatabaseHas('images', ['id' => $this->image->id]);
    }

    /**
     *
     * Caso erróneo post no encontrado
     */
    public function test_fail_delete_image_post_not_found()
    {
        $this->actingAs($this->user);

        $nonExistentPostId = Crypt::encrypt(9999);
        $response = $this->deleteJson("/api/v1/posts/{$nonExistentPostId}/images/{$this->encryptedImageId}");

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
        $response = $this->deleteJson("/api/v1/posts/{$this->encryptedPostId}/images/{$nonExistentImageId}");

        $response->assertStatus(404);
    }

    /**
     *
     * Caso erróneo post id alterado
     */
    public function test_fail_delete_image_with_tampered_post_id()
    {
        $this->actingAs($this->user);

        $tamperedPostId = substr($this->encryptedPostId, 0, -5) . 'abcde';
        $response = $this->deleteJson("/api/v1/posts/{$tamperedPostId}/images/{$this->encryptedImageId}");

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
        $response = $this->deleteJson("/api/v1/posts/{$this->encryptedPostId}/images/{$tamperedImageId}");

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
            'imageable_id' => $this->post->id,
            'imageable_type' => Post::class,
            'image_Uuid' => 'posts/images/image2.jpg'
        ]);
        $secondImageEncryptedId = Crypt::encrypt($secondImage->id);
        Storage::disk('public')->put($secondImage->image_Uuid, 'dummy');

        $response = $this->deleteJson("/api/v1/posts/{$this->encryptedPostId}/images/{$this->encryptedImageId}");

        $response->assertStatus(200);

        // Verificar que solo se eliminó la imagen especificada
        Storage::disk('public')->assertMissing($this->image->image_Uuid);
        Storage::disk('public')->assertExists($secondImage->image_Uuid);

        $this->assertDatabaseMissing('images', ['id' => $this->image->id]);
        $this->assertDatabaseHas('images', ['id' => $secondImage->id]);
    }
}
