<?php

namespace Tests\Feature\Admin\V1;

use App\Models\V1\Comment;
use App\Models\V1\Image;
use App\Models\V1\Post;
use App\Models\V1\ReplayComment;
use App\Models\V1\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CommentDeleteAdminTest extends TestCase
{
    use RefreshDatabase;

    private $user;
    private $post;
    private $comment;
    private $encryptedId;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Role::create(['name' => 'admin']);

        $this->user = User::factory()->create();
        $this->post = Post::factory()->create(['user_id' => $this->user->id]);
        $this->comment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'user_id' => $this->user->id
        ]);
        $this->encryptedId = Crypt::encrypt($this->comment->id);
    }

    /**
     * Caso exitoso eliminar comentario sin imágenes
     */
    public function test_successfully_delete_comment_without_images()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $response = $this->deleteJson("/api/v1/comments/{$this->encryptedId}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('comments', ['id' => $this->comment->id]);
    }

    /**
     * Caso exitoso eliminar comentario con imágenes
     */
    public function test_successfully_delete_comment_with_images()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        // Crear imágenes asociadas al comentario
        $image = Image::factory()->create([
            'imageable_id' => $this->comment->id,
            'imageable_type' => Comment::class,
            'image_Uuid' => 'comments/images/image1.jpg'
        ]);

        Storage::disk('public')->put($image->image_Uuid, 'dummy');

        $response = $this->deleteJson("/api/v1/comments/{$this->encryptedId}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('comments', ['id' => $this->comment->id]);
        Storage::disk('public')->assertMissing($image->image_Uuid);
    }

    /**
     * Caso erróneo eliminar comentario siendo cliente y no admin
     */
    public function test_fail_delete_comment_not_owned()
    {
        $otherUser = User::factory()->create();
        $this->actingAs($otherUser);

        $response = $this->deleteJson("/api/v1/comments/{$this->encryptedId}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('comments', ['id' => $this->comment->id]);
    }

    /**
     * Caso erróneo eliminar comentario por el admin (debería ser exitoso)
     */
    public function test_admin_can_delete_any_comment()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $response = $this->deleteJson("/api/v1/comments/{$this->encryptedId}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('comments', ['id' => $this->comment->id]);
    }

    /**
     * Caso erróneo ID alterado
     */
    public function test_fail_delete_with_tampered_id()
    {
        $this->actingAs($this->user);

        $tamperedId = substr($this->encryptedId, 0, -5) . 'abcde';

        $response = $this->deleteJson("/api/v1/comments/{$tamperedId}");

        $response->assertStatus(500);
    }

    /**
     * Caso erróneo comentario no encontrado
     */
    public function test_fail_delete_non_existent_comment()
    {
        $this->actingAs($this->user);

        $nonExistentId = Crypt::encrypt(999999);
        $response = $this->deleteJson("/api/v1/comments/{$nonExistentId}");

        $response->assertStatus(404);
    }
    /**
     * Caso adicional: Eliminar comentario con respuestas e imágenes
     */
}
