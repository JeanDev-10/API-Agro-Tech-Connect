<?php

namespace Tests\Feature\Comment\V1;

use App\Models\V1\ReplayComment;
use App\Models\V1\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission ;
use Tests\TestCase;

class ReplayCommentComplaintTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $admin;
    protected ReplayComment $comment;
    protected string $token;
    protected string $encryptedReplayCommentId;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear usuario admin (ID 1 como en el seeder)
        $this->admin = User::factory()->create(['id' => 1, 'email' => 'admin@example.com']);

        // Crear usuario regular
        $this->user = User::factory()->create();

        // Crear post para denunciar
        $this->comment = ReplayComment::factory()->create();

        // Generar ID cifrado del post
        $this->encryptedReplayCommentId = Crypt::encrypt($this->comment->id);

        // Generar token de autenticación
        $this->token = $this->user->createToken('test-token')->plainTextToken;

        // crear permiso
        Permission::create(['name' => 'replyComment.create-complaint']);
        // Asignar permisos al usuario
        $this->user->givePermissionTo('replyComment.create-complaint');

        // Preparar mocks para eventos y notificaciones
        Event::fake();
        Notification::fake();
    }

    public function test_user_can_report_a_replaycomment_successfully(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson("/api/v1/replaycomments/{$this->encryptedReplayCommentId}/complaint", [
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
            'complaintable_type' => ReplayComment::class,
            'description' => 'contenido inapropiado' // Se guarda en minúsculas
        ]);

        // Verificar que se disparó el evento
        Event::assertDispatched(\App\Events\V1\UserCreateReplayCommentComplaintEvent::class);
    }

    public function test_report_fails_with_invalid_encrypted_replaycomment_id(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/v1/replaycomments/invalid-encrypted-id/complaint', [
            'description' => 'Contenido inapropiado'
        ]);

        $response->assertStatus(500);
    }

    public function test_report_fails_when_replaycomment_does_not_exist(): void
    {
        $nonExistentId = 9999;
        $encryptedId = Crypt::encrypt($nonExistentId);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson("/api/v1/replaycomments/{$encryptedId}/complaint", [
            'description' => 'Contenido inapropiado'
        ]);

        $response->assertStatus(404);
    }

    public function test_user_cannot_report_same_replaycomment_more_than_5_times(): void
    {
        // Crear 5 denuncias
        for ($i = 0; $i < 5; $i++) {
            $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
            ])->postJson("/api/v1/replaycomments/{$this->encryptedReplayCommentId}/complaint", [
                'description' => 'Contenido inapropiado ' . $i
            ]);
        }

        // Intentar la 6ta denuncia
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson("/api/v1/replaycomments/{$this->encryptedReplayCommentId}/complaint", [
            'description' => 'Contenido inapropiado'
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Has alcanzado el límite de denuncias para esta respuesta a comentario',
                'error' => true
            ]);

        $this->assertDatabaseCount('complaints', 5);
    }

    public function test_report_fails_with_invalid_description(): void
    {
        // Descripción vacía
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson("/api/v1/replaycomments/{$this->encryptedReplayCommentId}/complaint", [
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
        ])->postJson("/api/v1/replaycomments/{$this->encryptedReplayCommentId}/complaint", [
            'description' => $longDescription
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Error de validación',
                'error' => true,
            ]);
    }

    public function test_admin_cannot_report_replaycomments(): void
    {
        $adminToken = $this->admin->createToken('admin-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $adminToken,
        ])->postJson("/api/v1/replaycomments/{$this->encryptedReplayCommentId}/complaint", [
            'description' => 'Contenido inapropiado'
        ]);

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_report_comments(): void
    {
        $response = $this->postJson("/api/v1/replaycomments/{$this->encryptedReplayCommentId}/complaint", [
            'description' => 'Contenido inapropiado'
        ]);

        $response->assertStatus(401);
    }

}
