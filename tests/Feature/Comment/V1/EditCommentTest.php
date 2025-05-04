<?php

namespace Tests\Feature\Comment\V1;

use App\Models\V1\Comment;
use App\Models\V1\Post;
use App\Models\V1\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EditCommentTest extends TestCase
{
    use RefreshDatabase;
    private $user;
    private $post;
    private $comment;
    private $encryptedPostId;
    private $encryptedCommentId;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Event::fake();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->post = Post::factory()->create();
        $this->comment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'user_id' => $this->user->id
        ]);

        $this->encryptedPostId = Crypt::encrypt($this->post->id);
        $this->encryptedCommentId = Crypt::encrypt($this->comment->id);
    }


    public function test_update_comment_with_text_only_successfully()
    {
        $response = $this->putJson("/api/v1/posts/{$this->encryptedPostId}/comments/{$this->encryptedCommentId}", [
            'comment' => 'Comentario actualizado'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('comments', [
            'id' => $this->comment->id,
            'comment' => 'Comentario actualizado'
        ]);
    }


    public function test_update_comment_adding_images_to_comment_without_images()
    {
        $images = [
            UploadedFile::fake()->image('comment1.jpg'),
            UploadedFile::fake()->image('comment2.png')
        ];

        $response = $this->putJson("/api/v1/posts/{$this->encryptedPostId}/comments/{$this->encryptedCommentId}", [
            'comment' => 'Comentario con imágenes nuevas',
            'images' => $images
        ]);

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data.images'));
    }


    public function test_update_comment_replacing_existing_images()
    {
        // Crear imágenes existentes
        $oldImages = [
            UploadedFile::fake()->image('old1.jpg')->store('comments/images'),
            UploadedFile::fake()->image('old2.png')->store('comments/images')
        ];

        $this->comment->images()->createMany([
            ['image_Uuid' => $oldImages[0], 'url' => Storage::url($oldImages[0])],
            ['image_Uuid' => $oldImages[1], 'url' => Storage::url($oldImages[1])]
        ]);

        // Nuevas imágenes
        $newImages = [
            UploadedFile::fake()->image('new1.jpg'),
            UploadedFile::fake()->image('new2.png')
        ];

        $response = $this->putJson("/api/v1/posts/{$this->encryptedPostId}/comments/{$this->encryptedCommentId}", [
            'images' => $newImages
        ]);

        $response->assertStatus(200);

        // Verificar que las imágenes antiguas fueron eliminadas
        foreach ($oldImages as $oldImage) {
            Storage::disk('public')->assertMissing($oldImage);
        }

        // Verificar que solo existen las nuevas imágenes
        $this->assertCount(2, $response->json('data.images'));
    }


    public function test_cannot_update_another_users_comment()
    {
        $otherUser = User::factory()->create();
        $otherComment = Comment::factory()->create(['user_id' => $otherUser->id,"post_id"=>$this->post->id]);
        $encryptedOtherCommentId = Crypt::encrypt($otherComment->id);

        $response = $this->putJson("/api/v1/posts/{$this->encryptedPostId}/comments/{$encryptedOtherCommentId}", [
            'comment' => 'Intento de edición no autorizado'
        ]);

        $response->assertStatus(403);
    }


    public function test_validation_errors_on_comment_update()
    {
        // Comentario muy largo
        $response1 = $this->putJson("/api/v1/posts/{$this->encryptedPostId}/comments/{$this->encryptedCommentId}", [
            'comment' => str_repeat('a', 101)
        ]);
        $response1->assertStatus(422);

        // Imagen muy grande
        $largeImage = UploadedFile::fake()->image('large.jpg')->size(4000);
        $response2 = $this->putJson("/api/v1/posts/{$this->encryptedPostId}/comments/{$this->encryptedCommentId}", [
            'images' => [$largeImage]
        ]);
        $response2->assertStatus(422);

        // Demasiadas imágenes
        $manyImages = array_fill(0, 6, UploadedFile::fake()->image('photo.jpg'));
        $response3 = $this->putJson("/api/v1/posts/{$this->encryptedPostId}/comments/{$this->encryptedCommentId}", [
            'images' => $manyImages
        ]);
        $response3->assertStatus(422);
    }


    public function test_fails_with_invalid_image_format()
    {
        $invalidFile = UploadedFile::fake()->create('document.pdf', 1000);

        $response = $this->putJson("/api/v1/posts/{$this->encryptedPostId}/comments/{$this->encryptedCommentId}", [
            'images' => [$invalidFile]
        ]);

        $response->assertStatus(422);
    }


    public function test_comment_not_found()
    {
        $nonExistentCommentId = Crypt::encrypt(9999);

        $response = $this->putJson("/api/v1/posts/{$this->encryptedPostId}/comments/{$nonExistentCommentId}", [
            'comment' => 'Comentario para comentario inexistente'
        ]);

        $response->assertStatus(404);
    }


    public function test_invalid_encrypted_comment_id()
    {
        $invalidId = 'invalid-encrypted-string';

        $response = $this->putJson("/api/v1/posts/{$this->encryptedPostId}/comments/{$invalidId}", [
            'comment' => 'Comentario con ID inválido'
        ]);

        $response->assertStatus(500);
    }
}
