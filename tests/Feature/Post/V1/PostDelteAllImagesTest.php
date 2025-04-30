<?php

namespace Tests\Feature\Post\V1;

use App\Models\V1\Image;
use App\Models\V1\Post;
use App\Models\V1\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PostDelteAllImagesTest extends TestCase
{
    private $user;
    private $post;
    private $encryptedId;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->user = User::factory()->create();
        $this->post = Post::factory()->create(['user_id' => $this->user->id]);
        $this->encryptedId = Crypt::encrypt($this->post->id);
    }

    /**
     *
     * Caso exitoso eliminar imágenes del post
     */
    public function test_successfully_delete_post_images()
    {
        $this->actingAs($this->user);

        // Crear imágenes asociadas al post
        $image1 = Image::factory()->create([
            'imageable_id' => $this->post->id,
            'imageable_type' => Post::class,
            'image_Uuid' => 'posts/images/image1.jpg'
        ]);

        $image2 = Image::factory()->create([
            'imageable_id' => $this->post->id,
            'imageable_type' => Post::class,
            'image_Uuid' => 'posts/images/image2.jpg'
        ]);

        // Crear archivos falsos en el storage
        Storage::disk('public')->put($image1->image_Uuid, 'dummy');
        Storage::disk('public')->put($image2->image_Uuid, 'dummy');

        $response = $this->deleteJson("/api/v1/posts/{$this->encryptedId}/images");

        $response->assertStatus(200);

        // Verificar eliminación de imágenes del storage
        Storage::disk('public')->assertMissing($image1->image_Uuid);
        Storage::disk('public')->assertMissing($image2->image_Uuid);

        // Verificar eliminación de registros en BD
        $this->assertDatabaseMissing('images', ['id' => $image1->id]);
        $this->assertDatabaseMissing('images', ['id' => $image2->id]);

        // Verificar que el post sigue existiendo
        $this->assertDatabaseHas('posts', ['id' => $this->post->id]);
    }

    /**
     *
     * Caso exitoso eliminar imágenes cuando el post no tiene imágenes
     */
    public function test_successfully_delete_images_from_post_without_images()
    {
        $this->actingAs($this->user);

        $response = $this->deleteJson("/api/v1/posts/{$this->encryptedId}/images");

        $response->assertStatus(200);
    }

    /**
     *
     * Caso erróneo eliminar imágenes de post que no me pertenece
     */
    public function test_fail_delete_images_from_another_users_post()
    {
        $otherUser = User::factory()->create();
        $this->actingAs($otherUser);

        $response = $this->deleteJson("/api/v1/posts/{$this->encryptedId}/images");

        $response->assertStatus(403);
    }

    /**
     *
     * Caso erróneo post no encontrado
     */
    public function test_fail_delete_images_from_non_existent_post()
    {
        $this->actingAs($this->user);
        $nonExistentId = Crypt::encrypt(999999);

        $response = $this->deleteJson("/api/v1/posts/{$nonExistentId}/images");

        $response->assertStatus(404);
    }

    /**
     *
     * Caso erróneo id alterado
     */
    public function test_fail_delete_images_with_tampered_id()
    {
        $this->actingAs($this->user);
        $tamperedId = substr($this->encryptedId, 0, -5) . 'abcde'; // Alterar el ID

        $response = $this->deleteJson("/api/v1/posts/{$tamperedId}/images");

        $response->assertStatus(500);
    }

    /**
     *
     * Caso adicional: Verificar que solo se eliminan imágenes del post
     */
    public function test_only_post_images_are_deleted()
    {
        $this->actingAs($this->user);

        // Imagen del post
        $postImage = Image::factory()->create([
            'imageable_id' => $this->post->id,
            'imageable_type' => Post::class,
            'image_Uuid' => 'posts/images/post1.jpg'
        ]);

        // Imagen de otro modelo (no debe eliminarse)
        $otherImage = Image::factory()->create([
            'image_Uuid' => 'users/images/user1.jpg'
        ]);

        Storage::disk('public')->put($postImage->image_Uuid, 'dummy');
        Storage::disk('public')->put($otherImage->image_Uuid, 'dummy');

        $response = $this->deleteJson("/api/v1/posts/{$this->encryptedId}/images");

        $response->assertStatus(200);

        // Verificar que solo se eliminó la imagen del post
        Storage::disk('public')->assertMissing($postImage->image_Uuid);
        Storage::disk('public')->assertExists($otherImage->image_Uuid);

        $this->assertDatabaseMissing('images', ['id' => $postImage->id]);
        $this->assertDatabaseHas('images', ['id' => $otherImage->id]);
    }
}
