<?php

namespace Tests\Feature\V1\User;

use App\Models\V1\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Crypt;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected string $token;
    protected array $notifications = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test-token')->plainTextToken;

        $this->createTestNotifications();
    }

    protected function createTestNotifications(int $count = 5): void
    {
        $follower = User::factory()->create(); // Crear un usuario seguidor

        for ($i = 0; $i < $count; $i++) {
            // Crear notificación
            $notification = new DatabaseNotification([
                'id' => \Illuminate\Support\Str::uuid()->toString(),
                'type' => 'App\Notifications\V1\NewFollowerNotification',
                'notifiable_type' => get_class($this->user),
                'notifiable_id' => $this->user->id,
                'data' => [
                    'message' => $follower->name . ' te ha comenzado a seguir',
                    'follower_id' => $follower->id,
                    'url_avatar' => null,
                    'follower_name' => $follower->name,
                    'type' => 'new_follower'
                ],
                'read_at' => ($i % 2 === 0) ? now() : null,
            ]);

            $notification->save();
            $this->notifications[] = $notification;
        }

        // Recargar relaciones
        $this->user->load('notifications');
    }


    public function test_get_user_notifications(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/notifications');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'statusCode',
                'error',
                'data' => [
                    'notifications' => [],
                    'meta'
                ]
            ])
            ->assertJsonPath('data.meta.notifications_count.total', 5);
    }


    public function test_get_unread_notifications(): void
    {
        Sanctum::actingAs($this->user);
        new DatabaseNotification([
            'id' => \Illuminate\Support\Str::uuid()->toString(),
            'type' => 'App\Notifications\V1\NewFollowerNotification',
            'notifiable_type' => get_class($this->user),
            'notifiable_id' => $this->user->id,
            'data' => [
                'message' =>  ' te ha comenzado a seguir',
                'follower_id' => "1",
                'url_avatar' => null,
                'follower_name' => "asdsa",
                'type' => 'new_follower'
            ],
            'read_at' => null]);
        $response = $this->getJson('/api/v1/notifications/unread');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'statusCode',
                'error',
                'data' => [
                    'notifications' => [],
                    'meta'
                ]
            ])
            ->assertJsonPath('data.meta.notifications_count.unread', 2);
    }


    public function test_show_notification(): void
    {
        Sanctum::actingAs($this->user);

        $notification = $this->notifications[0]; // Usar la primera notificación creada
        $encryptedId = Crypt::encrypt($notification->id);

        $response = $this->getJson("/api/v1/notifications/{$encryptedId}");

        $response->assertStatus(200);
    }


    public function test_show_notification_fails_with_invalid_id(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/notifications/invalid-id');

        $response->assertStatus(500)
            ->assertJson([
                'error' => true,
                'message' => 'Error al obtener la notificación: The payload is invalid.'
            ]);
    }


    public function test_show_notification_not_found(): void
    {
        Sanctum::actingAs($this->user);

        $nonExistentId = Crypt::encrypt('00000000-0000-0000-0000-000000000000');

        $response = $this->getJson("/api/v1/notifications/{$nonExistentId}");

        $response->assertStatus(404);

    }


    public function test_mark_notification_as_read(): void
    {
        Sanctum::actingAs($this->user);

        // Encontrar una notificación no leída
        $unreadNotification = $this->user->notifications()
            ->whereNull('read_at')
            ->first();

        $encryptedId = Crypt::encrypt($unreadNotification->id);

        $response = $this->putJson("/api/v1/notifications/{$encryptedId}/read");

        $response->assertStatus(200)
            ->assertJson([
                'error' => false,
            ]);

        $this->assertNotNull($unreadNotification->fresh()->read_at);
    }


    public function test_mark_notification_as_read_fails_when_already_read(): void
    {
        Sanctum::actingAs($this->user);

        // Encontrar una notificación leída
        $readNotification = $this->user->notifications()
            ->whereNotNull('read_at')
            ->first();

        $encryptedId = Crypt::encrypt($readNotification->id);

        $response = $this->putJson("/api/v1/notifications/{$encryptedId}/read");

        $response->assertStatus(404)
            ->assertJson([
                'error' => true,
                'message' => 'Notificación no encontrada o ya leída'
            ]);
    }


    public function test_mark_all_notifications_as_read(): void
    {
        Sanctum::actingAs($this->user);

        $unreadCount = $this->user->unreadNotifications()->count();

        $response = $this->putJson('/api/v1/notifications/read-all');

        $response->assertStatus(200)
            ->assertJson([
                'error' => false,
                'message' => 'Todas las notificaciones marcadas como leídas'
            ]);

        $this->assertEquals(0, $this->user->fresh()->unreadNotifications()->count());
    }


    public function test_notification_endpoints_require_authentication(): void
    {
        // Crear una notificación de prueba directamente
        $notification = DatabaseNotification::create([
            'id' => \Illuminate\Support\Str::uuid()->toString(),
            'type' => 'App\Notifications\V1\NewFollowerNotification',
            'notifiable_type' => get_class($this->user),
            'notifiable_id' => $this->user->id,
            'data' => []
        ]);

        $encryptedId = Crypt::encrypt($notification->id);

        // Test para /notifications
        $this->getJson('/api/v1/notifications')->assertStatus(401);

        // Test para /notifications/unread
        $this->getJson('/api/v1/notifications/unread')->assertStatus(401);

        // Test para /notifications/{id}
        $this->getJson("/api/v1/notifications/{$encryptedId}")->assertStatus(401);

        // Test para /notifications/{id}/read
        $this->putJson("/api/v1/notifications/{$encryptedId}/read")->assertStatus(401);

        // Test para /notifications/read-all
        $this->putJson('/api/v1/notifications/read-all')->assertStatus(401);
    }
}
