<?php

namespace Tests\Feature\Post\V1;

use App\Models\V1\Post;
use App\Models\V1\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;


class PostUpdateTest extends TestCase
{
    use RefreshDatabase;

    private $user;
    private $post;
    private $encryptedId;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Event::fake();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        $this->post = Post::factory()->create(['user_id' => $this->user->id]);
        $this->encryptedId = Crypt::encrypt($this->post->id);
    }


    public function test_update_post_with_title_and_description_successfully()
    {
        $response = $this->postJson("/api/v1/posts/{$this->encryptedId}", [
            'title' => 'Título actualizado',
            'description' => 'Descripción actualizada'
        ]);

        $response->assertStatus(200);


        $this->assertDatabaseHas('posts', [
            'id' => $this->post->id,
            'title' => 'Título actualizado',
            'description' => 'Descripción actualizada'
        ]);
    }


    public function test_update_post_adding_images_to_post_without_images()
    {
        $images = [
            UploadedFile::fake()->image('new1.jpg'),
            UploadedFile::fake()->image('new2.png')
        ];

        $response = $this->postJson("/api/v1/posts/{$this->encryptedId}", [
            'title' => 'Título con nuevas imágenes',
            'images' => $images
        ]);

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data.images'));
    }


    public function test_update_post_add_existing_images()
    {
        // Crear imágenes existentes
        $oldImages = [
            UploadedFile::fake()->image('old1.jpg')->store('posts/images'),
            UploadedFile::fake()->image('old2.png')->store('posts/images')
        ];

        $this->post->images()->createMany([
            ['image_Uuid' => $oldImages[0], 'url' => Storage::url($oldImages[0])],
            ['image_Uuid' => $oldImages[1], 'url' => Storage::url($oldImages[1])]
        ]);

        // Nuevas imágenes
        $newImages = [
            UploadedFile::fake()->image('new1.jpg'),
            UploadedFile::fake()->image('new2.png')
        ];

        $response = $this->postJson("/api/v1/posts/{$this->encryptedId}", [
            'images' => $newImages
        ]);

        $response->assertStatus(200);

        // Verificar que las imágenes antiguas fueron eliminadas
        foreach ($oldImages as $oldImage) {
            Storage::disk('public')->assertMissing($oldImage);
        }

        // Verificar que solo existen las nuevas imágenes
        $this->assertCount(4, $response->json('data.images'));
    }


    public function test_cannot_update_another_users_post()
    {
        $otherUser = User::factory()->create();
        $otherPost = Post::factory()->create(['user_id' => $otherUser->id]);
        $encryptedOtherId = Crypt::encrypt($otherPost->id);

        $response = $this->postJson("/api/v1/posts/{$encryptedOtherId}", [
            'title' => 'Intento de edición no autorizado'
        ]);

        $response->assertStatus(403);
    }


    public function test_validation_errors_on_update()
    {
        // Descripción muy larga
        $response1 = $this->postJson("/api/v1/posts/{$this->encryptedId}", [
            'description' => str_repeat('a', 3001)
        ]);
        $response1->assertStatus(422);

        // Imagen muy grande
        $largeImage = UploadedFile::fake()->image('large.jpg')->size(4000);
        $response2 = $this->postJson("/api/v1/posts/{$this->encryptedId}", [
            'images' => [$largeImage]
        ]);
        $response2->assertStatus(422);

        // Demasiadas imágenes
        $manyImages = array_fill(0, 11, UploadedFile::fake()->image('photo.jpg'));
        $response3 = $this->postJson("/api/v1/posts/{$this->encryptedId}", [
            'images' => $manyImages
        ]);
        $response3->assertStatus(422);
    }


    public function test_post_not_found()
    {
        $nonExistentId = Crypt::encrypt(9999);
        $response = $this->postJson("/api/v1/posts/{$nonExistentId}", [
            'title' => 'Título para post inexistente'
        ]);

        $response->assertStatus(404);
    }


    public function test_invalid_encrypted_id()
    {
        $invalidId = 'invalid-encrypted-string';
        $response = $this->postJson("/api/v1/posts/{$invalidId}", [
            'title' => 'Título con ID inválido'
        ]);

        $response->assertStatus(500);
    }
}
