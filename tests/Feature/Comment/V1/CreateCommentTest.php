<?php

namespace Tests\Feature\Comment\V1;

use App\Events\V1\NewCommentEvent;
use App\Models\V1\Post;
use App\Models\V1\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CreateCommentTest extends TestCase
{
    use RefreshDatabase;
    private $user;
    private $encryptedId;
    private $post;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Event::fake();
        $this->user = User::factory()->create();
        $this->post = Post::factory()->create(['user_id' => $this->user->id]);
        $this->encryptedId = encrypt($this->post->id);
        $this->actingAs($this->user);
    }


    public function test_create_comment_with_text_only_successfully()
    {
        $response = $this->postJson("/api/v1/posts/{$this->encryptedId}/comments", [
            'comment' => 'Este es un comentario de prueba'
        ]);

        $response->assertStatus(201);
    }


    public function test_create_comment_with_images_successfully()
    {
        $images = [
            UploadedFile::fake()->image('comment1.jpg'),
            UploadedFile::fake()->image('comment2.png')
        ];

        $response = $this->postJson("/api/v1/posts/{$this->encryptedId}/comments", [
            'comment' => 'Comentario con imágenes',
            'images' => $images
        ]);

        $response->assertStatus(201);
        $this->assertCount(2, $response->json('data.images'));
    }


    public function test_fails_when_comment_images_exceed_max_size()
    {
        $largeImage = UploadedFile::fake()->image('large.jpg')->size(4000); // 4MB

        $response = $this->postJson("/api/v1/posts/{$this->encryptedId}/comments", [
            'comment' => 'Comentario con imagen grande',
            'images' => [$largeImage]
        ]);

        $response->assertStatus(422);
    }


    public function test_fails_with_invalid_comment_image_format()
    {
        $invalidFile = UploadedFile::fake()->create('document.pdf', 1000);

        $response = $this->postJson("/api/v1/posts/{$this->encryptedId}/comments", [
            'comment' => 'Comentario con formato inválido',
            'images' => [$invalidFile]
        ]);

        $response->assertStatus(422);
    }


    public function test_fails_when_uploading_more_than_5_images()
    {
        $images = [];
        for ($i = 0; $i < 6; $i++) {
            $images[] = UploadedFile::fake()->image("photo$i.jpg");
        }

        $response = $this->postJson("/api/v1/posts/{$this->encryptedId}/comments", [
            'comment' => 'Comentario con muchas imágenes',
            'images' => $images
        ]);

        $response->assertStatus(422);
    }


    public function test_comment_validation_errors()
    {
        // Caso 1: Falta comentario
        $response1 = $this->postJson("/api/v1/posts/{$this->encryptedId}/comments", []);
        $response1->assertStatus(422);

        // Caso 2: Comentario muy largo
        $response2 = $this->postJson("/api/v1/posts/{$this->encryptedId}/comments", [
            'comment' => str_repeat('a', 1001)
        ]);
        $response2->assertStatus(422);
    }


    public function test_fails_when_post_not_found()
    {
        $nonExistentId = encrypt(999999);

        $response = $this->postJson("/api/v1/posts/{$nonExistentId}/comments", [
            'comment' => 'Comentario en post inexistente'
        ]);

        $response->assertStatus(404);
    }


    public function test_fails_with_tampered_id()
    {
        $tamperedId = 'invalid_encrypted_string';

        $response = $this->postJson("/api/v1/posts/{$tamperedId}/comments", [
            'comment' => 'Comentario con ID alterado'
        ]);

        $response->assertStatus(500);
    }
}
