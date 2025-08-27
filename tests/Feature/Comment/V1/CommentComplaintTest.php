<?php

namespace Tests\Feature\Comment\V1;

use App\Models\V1\Comment;
use App\Models\V1\Complaint;
use App\Models\V1\Post;
use App\Models\V1\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission ;
use Tests\TestCase;

class CommentComplaintTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $admin;
    protected Comment $comment;
    protected string $token;
    protected string $encryptedCommentId;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear usuario admin (ID 1 como en el seeder)
        $this->admin = User::factory()->create(['id' => 1, 'email' => 'admin@example.com']);

        // Crear usuario regular
        $this->user = User::factory()->create();

        // Crear post para denunciar
        $this->comment = Comment::factory()->create();

        // Generar ID cifrado del post
        $this->encryptedCommentId = Crypt::encrypt($this->comment->id);

        // Generar token de autenticación
        $this->token = $this->user->createToken('test-token')->plainTextToken;

        // crear permiso
        Permission::firstOrCreate(['name' => 'comment.create-complaint']);
        // Asignar permisos al usuario
        $this->user->givePermissionTo('comment.create-complaint');

        // Preparar mocks para eventos y notificaciones
        Event::fake();
        Notification::fake();
    }

    public function test_user_can_report_a_comment_successfully(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson("/api/v1/comments/{$this->encryptedCommentId}/complaint", [
            'description' => 'Contenido inapropiado'
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'La denuncia ha sido registrada correctamente',
                'error' => false
            ]);

        $this->assertDatabaseHas('complaints', [
            'user_id' => $this->user->id,
            'complaintable_id' => $this->comment->id,
            'complaintable_type' => Comment::class,
            'description' => 'contenido inapropiado' // Se guarda en minúsculas
        ]);

        // Verificar que se disparó el evento
        Event::assertDispatched(\App\Events\V1\UserCreateCommentComplaintEvent::class);
    }

    public function test_report_fails_with_invalid_encrypted_comment_id(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/v1/comments/invalid-encrypted-id/complaint', [
            'description' => 'Contenido inapropiado'
        ]);

        $response->assertStatus(500);
    }

    public function test_report_fails_when_comment_does_not_exist(): void
    {
        $nonExistentId = 9999;
        $encryptedId = Crypt::encrypt($nonExistentId);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson("/api/v1/comments/{$encryptedId}/complaint", [
            'description' => 'Contenido inapropiado'
        ]);

        $response->assertStatus(404);
    }

    public function test_user_cannot_report_same_comment_more_than_5_times(): void
    {
        // Crear 5 denuncias
        for ($i = 0; $i < 5; $i++) {
            $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
            ])->postJson("/api/v1/comments/{$this->encryptedCommentId}/complaint", [
                'description' => 'Contenido inapropiado ' . $i
            ]);
        }

        // Intentar la 6ta denuncia
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson("/api/v1/comments/{$this->encryptedCommentId}/complaint", [
            'description' => 'Contenido inapropiado'
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Has alcanzado el límite de denuncias para este comentario',
                'error' => true
            ]);

        $this->assertDatabaseCount('complaints', 5);
    }

    public function test_report_fails_with_invalid_description(): void
    {
        // Descripción vacía
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson("/api/v1/comments/{$this->encryptedCommentId}/complaint", [
            'description' => ''
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Error de validación',
                'error' => true,
            ]);

        // Descripción demasiado larga
        $longDescription = str_repeat('a', 101);
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson("/api/v1/comments/{$this->encryptedCommentId}/complaint", [
            'description' => $longDescription
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Error de validación',
                'error' => true,
            ]);
    }

    public function test_admin_cannot_report_comments(): void
    {
        $adminToken = $this->admin->createToken('admin-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $adminToken,
        ])->postJson("/api/v1/comments/{$this->encryptedCommentId}/complaint", [
            'description' => 'Contenido inapropiado'
        ]);

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_report_comments(): void
    {
        $response = $this->postJson("/api/v1/comments/{$this->encryptedCommentId}/complaint", [
            'description' => 'Contenido inapropiado'
        ]);

        $response->assertStatus(401);
    }

}
