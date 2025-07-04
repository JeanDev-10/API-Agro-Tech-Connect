<?php

namespace Tests\Feature\Comment\V1;

use App\Models\V1\Comment;
use App\Models\V1\Post;
use App\Models\V1\ReplayComment;
use App\Models\V1\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UpdateReplayCommentTest extends TestCase
{
    use RefreshDatabase;
    
    private $user;
    private $post;
    private $comment;
    private $reply;
    private $encryptedPostId;
    private $encryptedReplyId;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        
        $this->user = User::factory()->create();
        $this->post = Post::factory()->create();
        $this->comment = Comment::factory()->create(['post_id' => $this->post->id]);
        $this->reply = ReplayComment::factory()->create([
            'comment_id' => $this->comment->id,
            'user_id' => $this->user->id
        ]);
        
        $this->encryptedPostId = Crypt::encrypt($this->post->id);
        $this->encryptedReplyId = Crypt::encrypt($this->reply->id);
        $this->actingAs($this->user);
    }

    
    public function test_update_reply_with_text_only_successfully()
    {
        $response = $this->putJson(
            "/api/v1/posts/{$this->encryptedPostId}/replaycomments/{$this->encryptedReplyId}",
            ['comment' => 'Respuesta actualizada']
        );
        $response->assertStatus(200);
        $this->assertDatabaseHas('replay_comments', [
            'id' => $this->reply->id,
            'comment' => 'Respuesta actualizada'
        ]);
    }

    
    public function test_update_reply_adding_images_to_reply_without_images()
    {
        $images = [
            UploadedFile::fake()->image('reply1.jpg'),
            UploadedFile::fake()->image('reply2.png')
        ];

        $response = $this->putJson(
            "/api/v1/posts/{$this->encryptedPostId}/replaycomments/{$this->encryptedReplyId}",
            [
                'comment' => 'Respuesta con imágenes nuevas',
                'images' => $images
            ]
        );

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data.images'));
    }

    
    public function test_update_reply_replacing_existing_images()
    {
        // Crear imágenes existentes
        $oldImages = [
            UploadedFile::fake()->image('old1.jpg')->store('comments/replaycomments/images'),
            UploadedFile::fake()->image('old2.png')->store('comments/replaycomments/images')
        ];

        $this->reply->images()->createMany([
            ['image_Uuid' => $oldImages[0], 'url' => Storage::url($oldImages[0])],
            ['image_Uuid' => $oldImages[1], 'url' => Storage::url($oldImages[1])]
        ]);

        // Nuevas imágenes
        $newImages = [
            UploadedFile::fake()->image('new1.jpg'),
            UploadedFile::fake()->image('new2.png')
        ];

        $response = $this->putJson(
            "/api/v1/posts/{$this->encryptedPostId}/replaycomments/{$this->encryptedReplyId}",
            ['images' => $newImages]
        );

        $response->assertStatus(200);

        // Verificar que las imágenes antiguas fueron eliminadas
        foreach ($oldImages as $oldImage) {
            Storage::disk('public')->assertMissing($oldImage);
        }

        // Verificar que solo existen las nuevas imágenes
        $this->assertCount(2, $response->json('data.images'));
    }

    
    public function test_cannot_update_another_users_reply()
    {
        $otherUser = User::factory()->create();
        $otherReply = ReplayComment::factory()->create([
            'comment_id' => $this->comment->id,
            'user_id' => $otherUser->id
        ]);
        
        $encryptedOtherReplyId = Crypt::encrypt($otherReply->id);

        $response = $this->putJson(
            "/api/v1/posts/{$this->encryptedPostId}/replaycomments/{$encryptedOtherReplyId}",
            ['comment' => 'Intento de edición no autorizado']
        );

        $response->assertStatus(403);
    }

    
    public function test_reply_validation_errors_on_update()
    {
        // Respuesta muy larga
        $response1 = $this->putJson(
            "/api/v1/posts/{$this->encryptedPostId}/replaycomments/{$this->encryptedReplyId}",
            ['comment' => str_repeat('a', 101)]
        );
        $response1->assertStatus(422);

        // Imagen muy grande
        $largeImage = UploadedFile::fake()->image('large.jpg')->size(4000);
        $response2 = $this->putJson(
            "/api/v1/posts/{$this->encryptedPostId}/replaycomments/{$this->encryptedReplyId}",
            ['images' => [$largeImage]]
        );
        $response2->assertStatus(422);

        // Demasiadas imágenes
        $manyImages = array_fill(0, 6, UploadedFile::fake()->image('photo.jpg'));
        $response3 = $this->putJson(
            "/api/v1/posts/{$this->encryptedPostId}/replaycomments/{$this->encryptedReplyId}",
            ['images' => $manyImages]
        );
        $response3->assertStatus(422);
    }

    
    public function test_reply_not_found()
    {
        $nonExistentId = Crypt::encrypt(9999);
        $response = $this->putJson(
            "/api/v1/posts/{$this->encryptedPostId}/replaycomments/{$nonExistentId}",
            ['comment' => 'Respuesta para reply inexistente']
        );

        $response->assertStatus(404);
    }

    
    public function test_invalid_encrypted_reply_id()
    {
        $invalidId = 'invalid-encrypted-string';
        $response = $this->putJson(
            "/api/v1/posts/{$this->encryptedPostId}/replaycomments/{$invalidId}",
            ['comment' => 'Respuesta con ID inválido']
        );

        $response->assertStatus(500);
    }
}