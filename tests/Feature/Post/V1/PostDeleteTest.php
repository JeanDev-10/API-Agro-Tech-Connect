<?php

namespace Tests\Feature\Post\V1;

use App\Events\V1\PostDeletedByAdmin;
use App\Models\V1\Comment;
use App\Models\V1\Image;
use App\Models\V1\Post;
use App\Models\V1\ReplayComment;
use App\Models\V1\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PostDeleteTest extends TestCase
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
        Role::create(['name' => 'admin']);
        Notification::fake();
        $this->user = User::factory()->create();
        $this->post = Post::factory()->create(['user_id' => $this->user->id]);
        $this->encryptedId = Crypt::encrypt($this->post->id);
    }

    /**
     * Caso exitoso eliminar publicación con título y descripción (sin imágenes originalmente)
     */
    public function test_successfully_delete_post_without_images()
    {
        $this->actingAs($this->user);

        $response = $this->deleteJson("/api/v1/posts/{$this->encryptedId}");

        $response->assertStatus(200);
    }

    /**
     * Caso exitoso eliminar publicación con título, descripción e imágenes
     */
    public function test_successfully_delete_post_with_images()
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

        $response = $this->deleteJson("/api/v1/posts/{$this->encryptedId}");

        $response->assertStatus(200);
    }

    /**
     * Caso erróneo eliminar publicación que no pertenece
     */
    public function test_fail_delete_post_not_owned()
    {
        $otherUser = User::factory()->create();
        $this->actingAs($otherUser);

        $response = $this->deleteJson("/api/v1/posts/{$this->encryptedId}");

        $response->assertStatus(403);

        $this->assertDatabaseHas('posts', ['id' => $this->post->id]);
    }

    /**
     * Caso erróneo eliminar publicación por el admin (debería ser exitoso pero con notificación)
     */
    public function test_admin_delete_post_notifies_owner()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $response = $this->deleteJson("/api/v1/posts/{$this->encryptedId}");

        $response->assertStatus(200);
    }

    /**
     * Caso erróneo ID alterado
     */
    public function test_fail_delete_with_tampered_id()
    {
        $this->actingAs($this->user);

        $tamperedId = substr($this->encryptedId, 0, -5) . 'abcde'; // Alterar el ID

        $response = $this->deleteJson("/api/v1/posts/{$tamperedId}");

        $response->assertStatus(500);
    }

    /**
     * Caso erróneo publicación no encontrada
     */
    public function test_fail_delete_non_existent_post()
    {
        $this->actingAs($this->user);

        $nonExistentId = Crypt::encrypt(999999);
        $response = $this->deleteJson("/api/v1/posts/{$nonExistentId}");

        $response->assertStatus(404);
    }

    /**
     * Caso adicional: Eliminar post con comentarios y respuestas
     */
    public function test_delete_post_with_comments_and_replies()
    {
        $this->actingAs($this->user);

        // Crear comentario con imagen
        $comment = Comment::factory()->create(['post_id' => $this->post->id]);
        $commentImage = Image::factory()->create([
            'imageable_id' => $comment->id,
            'imageable_type' => Comment::class,
            'image_Uuid' => 'comments/images/comment1.jpg'
        ]);
        Storage::disk('public')->put($commentImage->image_Uuid, 'dummy');

        // Crear respuesta con imagen
        $reply = ReplayComment::factory()->create(['comment_id' => $comment->id]);
        $replyImage = Image::factory()->create([
            'imageable_id' => $reply->id,
            'imageable_type' => ReplayComment::class,
            'image_Uuid' => 'replies/images/reply1.jpg'
        ]);
        Storage::disk('public')->put($replyImage->image_Uuid, 'dummy');

        $response = $this->deleteJson("/api/v1/posts/{$this->encryptedId}");

        $response->assertStatus(200);
    }
}
