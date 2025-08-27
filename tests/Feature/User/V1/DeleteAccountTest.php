<?php

namespace Tests\Feature\V1\User;

use App\Events\V1\UserDeletedAccountEvent;
use App\Models\V1\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Illuminate\Support\Facades\Event;

class DeleteAccountTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear roles y permisos como en el seeder
        $adminRole = Role::create(['name' => 'admin']);
        $clientRole = Role::create(['name' => 'client']);
        $clientSocialRole = Role::create(['name' => 'client_social']);

        Permission::create(['name' => 'user.change-password']);
        Permission::create(['name' => 'user.delete-account']);
        Permission::create(['name' => 'user.delete-account-social']);

        $adminRole->syncPermissions(Permission::all());
        $clientRole->syncPermissions(['user.change-password', 'user.delete-account']);
        $clientSocialRole->syncPermissions(['user.delete-account-social']);
        Event::fake();
    }

    public function test_local_user_can_delete_account_with_correct_password()
    {
        $user = User::factory()->create([
            'registration_method' => 'local',
            'password' => Hash::make('Password123!'),
        ]);
        $user->assignRole('client');

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson('/api/v1/me', [
            'password' => 'Password123!'
        ]);

        $response->assertStatus(204);
    }

    public function test_social_user_can_delete_account_without_password()
    {
        $user = User::factory()->create([
            'registration_method' => 'google',
            'password' => null,
        ]);
        $user->assignRole('client_social');

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson('/api/v1/me/social');

        $response->assertStatus(204);
    }

    public function test_local_user_cannot_delete_account_with_wrong_password()
    {
        $user = User::factory()->create([
            'registration_method' => 'local',
            'password' => Hash::make('Password123!'),
        ]);
        $user->assignRole('client');

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson('/api/v1/me', [
            'password' => 'Wronssword123!'
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Error de validación',
                'error' => true,
                'data' => [
                    'password' => ['La contraseña actual no es correcta.']
                ]
            ]);
    }

    public function test_local_user_cannot_delete_account_with_invalid_password_format()
    {
        $user = User::factory()->create([
            'registration_method' => 'local',
            'password' => Hash::make('Password123!'),
        ]);
        $user->assignRole('client');

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson('/api/v1/me', [
            'password' => 'simple'
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Error de validación',
                'error' => true,
            ]);
    }

    public function test_local_user_cannot_use_social_endpoint_to_delete_account()
    {
        $user = User::factory()->create([
            'registration_method' => 'local',
            'password' => Hash::make('Password123!'),
        ]);
        $user->assignRole('client');

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson('/api/v1/me/social');

        $response->assertStatus(403);
    }

    public function test_social_user_cannot_use_local_endpoint_to_delete_account()
    {
        $user = User::factory()->create([
            'registration_method' => 'google',
            'password' => null,
        ]);
        $user->assignRole('client_social');

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson('/api/v1/me', [
            'password' => 'anypassword'
        ]);

        $response->assertStatus(403);
    }

    public function test_cannot_delete_account_without_authentication()
    {
        $response = $this->putJson('/api/v1/me', [
            'password' => 'Password123!'
        ]);
        $response->assertStatus(401);

        $response = $this->putJson('/api/v1/me/social');
        $response->assertStatus(401);
    }

    public function test_local_user_must_provide_password_to_delete_account()
    {
        $user = User::factory()->create([
            'registration_method' => 'local',
            'password' => Hash::make('Password123!'),
        ]);
        $user->assignRole('client');

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson('/api/v1/me', []);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Error de validación',
                'error' => true,
                'data' => [
                    'password' => ['El campo contraseña es obligatorio.']
                ]
            ]);
    }

    public function test_user_without_permission_cannot_delete_account()
    {
        // Usuario sin rol asignado
        $user = User::factory()->create([
            'registration_method' => 'local',
            'password' => Hash::make('Password123!'),
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson('/api/v1/me', [
            'password' => 'Password123!'
        ]);

        $response->assertStatus(403);
    }

    public function test_social_user_without_permission_cannot_delete_account()
    {
        // Usuario social sin rol asignado
        $user = User::factory()->create([
            'registration_method' => 'google',
            'password' => null,
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson('/api/v1/me/social');

        $response->assertStatus(403);
    }

    public function test_user_deleted_account_event_not_dispatched_on_failure()
    {
        $user = User::factory()->create([
            'registration_method' => 'local',
            'password' => Hash::make('Password123!'),
        ]);
        $user->assignRole('client');

        $token = $user->createToken('test-token')->plainTextToken;

        // Intentar con contraseña incorrecta
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson('/api/v1/me', [
            'password' => 'WrongPassword123!'
        ]);

        $response->assertStatus(422);

        // Verificar que el evento NO fue disparado
        Event::assertNotDispatched(UserDeletedAccountEvent::class);
    }

    public function test_event_contains_correct_user_data()
    {
        $user = User::factory()->create([
            'registration_method' => 'local',
            'password' => Hash::make('Password123!'),
            'name' => 'Test',
            'lastname' => 'User',
            'email' => 'test@example.com',
        ]);
        $user->assignRole('client');

        $token = $user->createToken('test-token')->plainTextToken;

        $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson('/api/v1/me', [
            'password' => 'Password123!'
        ]);

        // Verificar los datos específicos del usuario en el evento
        Event::assertDispatched(UserDeletedAccountEvent::class, function ($event) use ($user) {
            return $event->user->name === 'Test' &&
                   $event->user->lastname === 'User' &&
                   $event->user->email === 'test@example.com' &&
                   $event->user->registration_method === 'local';
        });
    }
}
