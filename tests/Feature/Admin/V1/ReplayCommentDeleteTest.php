<?php

namespace Tests\Feature\Admin\V1;

use App\Models\V1\Comment;
use App\Models\V1\Image;
use App\Models\V1\ReplayComment;
use App\Models\V1\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReplayCommentDeleteTest extends TestCase
{
    use RefreshDatabase;

    private $user;
    private $comment;
    private $replayComment;
    private $encryptedId;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Role::create(['name' => 'admin']);

        $this->user = User::factory()->create();
        $this->comment = Comment::factory()->create(['user_id' => $this->user->id]);
        $this->replayComment = ReplayComment::factory()->create([
            'comment_id' => $this->comment->id,
            'user_id' => $this->user->id
        ]);
        $this->encryptedId = Crypt::encrypt($this->replayComment->id);
    }

    /**
     * Caso exitoso eliminar respuesta sin imágenes
     */
    public function test_successfully_delete_replay_without_images()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $response = $this->deleteJson("/api/v1/replaycomments/{$this->encryptedId}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('replay_comments', ['id' => $this->replayComment->id]);
    }

    /**
     * Caso exitoso eliminar respuesta con imágenes
     */
    public function test_successfully_delete_replay_with_images()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        // Crear imágenes asociadas a la respuesta
        $image = Image::factory()->create([
            'imageable_id' => $this->replayComment->id,
            'imageable_type' => ReplayComment::class,
            'image_Uuid' => 'replay-comments/images/image1.jpg'
        ]);

        Storage::disk('public')->put($image->image_Uuid, 'dummy');

        $response = $this->deleteJson("/api/v1/replaycomments/{$this->encryptedId}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('replay_comments', ['id' => $this->replayComment->id]);
        Storage::disk('public')->assertMissing($image->image_Uuid);
    }

    /**
     * Caso erróneo eliminar respuesta sin ser admin
     */
    public function test_fail_delete_replay_not_owned()
    {
        $otherUser = User::factory()->create();
        $this->actingAs($otherUser);

        $response = $this->deleteJson("/api/v1/replaycomments/{$this->encryptedId}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('replay_comments', ['id' => $this->replayComment->id]);
    }

    /**
     * Caso erróneo eliminar respuesta por el admin (debería ser exitoso)
     */
    public function test_admin_can_delete_any_replay()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $response = $this->deleteJson("/api/v1/replaycomments/{$this->encryptedId}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('replay_comments', ['id' => $this->replayComment->id]);
    }

    /**
     * Caso erróneo ID alterado
     */
    public function test_fail_delete_with_tampered_id()
    {
        $this->actingAs($this->user);

        $tamperedId = substr($this->encryptedId, 0, -5) . 'abcde';

        $response = $this->deleteJson("/api/v1/replaycomments/{$tamperedId}");

        $response->assertStatus(500);
    }

    /**
     * Caso erróneo respuesta no encontrada
     */
    public function test_fail_delete_non_existent_replay()
    {
        $this->actingAs($this->user);

        $nonExistentId = Crypt::encrypt(999999);
        $response = $this->deleteJson("/api/v1/replaycomments/{$nonExistentId}");

        $response->assertStatus(404);
    }
}
