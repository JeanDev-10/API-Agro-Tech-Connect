<?php

namespace Tests\Feature\Comment\V1;

use App\Events\V1\NewReplyEvent;
use App\Models\V1\Comment;
use App\Models\V1\Post;
use App\Models\V1\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StoreReplayCommentTest extends TestCase
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
        $this->post = Post::factory()->create(['user_id' => $this->user->id]);
        $this->comment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'user_id' => User::factory()->create()->id // Dueño diferente
        ]);
        
        $this->encryptedPostId = Crypt::encrypt($this->post->id);
        $this->encryptedCommentId = Crypt::encrypt($this->comment->id);
        $this->actingAs($this->user);
    }

    
    public function test_create_reply_with_text_only_successfully()
    {
        $response = $this->postJson(
            "/api/v1/posts/{$this->encryptedPostId}/comments/{$this->encryptedCommentId}/replaycomments", 
            ['comment' => 'Esta es una respuesta']
        );

        $response->assertStatus(201);
        $this->assertDatabaseHas('replay_comments', [
            'comment_id' => $this->comment->id,
            'comment' => 'Esta es una respuesta'
        ]);
    }

    
    public function test_create_reply_with_images_successfully()
    {
        $images = [
            UploadedFile::fake()->image('reply1.jpg'),
            UploadedFile::fake()->image('reply2.png')
        ];

        $response = $this->postJson(
            "/api/v1/posts/{$this->encryptedPostId}/comments/{$this->encryptedCommentId}/replaycomments",
            [
                'comment' => 'Respuesta con imágenes',
                'images' => $images
            ]
        );

        $response->assertStatus(201);
        $this->assertCount(2, $response->json('data.images'));
    }

    
    public function test_notifies_comment_owner_when_replied()
    {
        $this->postJson(
            "/api/v1/posts/{$this->encryptedPostId}/comments/{$this->encryptedCommentId}/replaycomments",
            ['comment' => 'Esta respuesta debería notificar']
        );

        Event::assertDispatched(NewReplyEvent::class, function ($event) {
            return $event->parentComment->id === $this->comment->id;
        });
    }

    
    public function test_fails_when_reply_exceeds_max_length()
    {
        $response = $this->postJson(
            "/api/v1/posts/{$this->encryptedPostId}/comments/{$this->encryptedCommentId}/replaycomments",
            ['comment' => str_repeat('a', 101)]
        );

        $response->assertStatus(422);
    }

    
    public function test_fails_with_invalid_image_format()
    {
        $invalidFile = UploadedFile::fake()->create('document.pdf', 1000);

        $response = $this->postJson(
            "/api/v1/posts/{$this->encryptedPostId}/comments/{$this->encryptedCommentId}/replaycomments",
            [
                'comment' => 'Respuesta con archivo inválido',
                'images' => [$invalidFile]
            ]
        );

        $response->assertStatus(422);
    }

    
    public function test_fails_when_images_exceed_max_size()
    {
        $largeImage = UploadedFile::fake()->image('large.jpg')->size(4000); // 4MB

        $response = $this->postJson(
            "/api/v1/posts/{$this->encryptedPostId}/comments/{$this->encryptedCommentId}/replaycomments",
            [
                'comment' => 'Respuesta con imagen grande',
                'images' => [$largeImage]
            ]
        );

        $response->assertStatus(422);
    }

    
    public function test_fails_when_uploading_more_than_5_images()
    {
        $images = array_fill(0, 6, UploadedFile::fake()->image('photo.jpg'));

        $response = $this->postJson(
            "/api/v1/posts/{$this->encryptedPostId}/comments/{$this->encryptedCommentId}/replaycomments",
            [
                'comment' => 'Respuesta con muchas imágenes',
                'images' => $images
            ]
        );

        $response->assertStatus(422);
    }

    
    public function test_reply_validation_errors()
    {
        // Caso 1: Falta comentario
        $response1 = $this->postJson(
            "/api/v1/posts/{$this->encryptedPostId}/comments/{$this->encryptedCommentId}/replaycomments", 
            []
        );
        $response1->assertStatus(422);

        // Caso 2: Comentario muy largo
        $response2 = $this->postJson(
            "/api/v1/posts/{$this->encryptedPostId}/comments/{$this->encryptedCommentId}/replaycomments",
            ['comment' => str_repeat('a', 101)]
        );
        $response2->assertStatus(422);
    }

    
    public function test_fails_when_comment_not_found()
    {
        $nonExistentId = Crypt::encrypt(9999);
        
        $response = $this->postJson(
            "/api/v1/posts/{$this->encryptedPostId}/comments/{$nonExistentId}/replaycomments",
            ['comment' => 'Respuesta a comentario inexistente']
        );

        $response->assertStatus(404);
    }

    
    public function test_fails_with_tampered_id()
    {
        $tamperedId = 'invalid_encrypted_string';
        
        $response = $this->postJson(
            "/api/v1/posts/{$this->encryptedPostId}/comments/{$tamperedId}/replaycomments",
            ['comment' => 'Respuesta con ID alterado']
        );

        $response->assertStatus(500);
    }
}