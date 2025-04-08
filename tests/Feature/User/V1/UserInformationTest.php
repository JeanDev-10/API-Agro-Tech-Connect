<?php

namespace Tests\Feature\V1\User;

use App\Models\V1\User;
use App\Models\V1\UserInformation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;

class UserInformationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear roles y permisos necesarios
        $this->seed(\Database\Seeders\V1\UserSeeder::class);
    }

    public function test_user_can_store_new_information_successfully()
    {
        $user = User::factory()->create();
        $user->assignRole('client');
        Sanctum::actingAs($user);

        $data = [
            'description' => 'Esta es mi descripción personal',
            'link1' => 'https://github.com/miusuario',
            'link2' => 'https://linkedin.com/in/miusuario',
            'link3' => null,
        ];

        $response = $this->postJson('/api/v1/me/user-information', $data);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Información del usuario actualizada correctamente',
                'error' => false,
                'data' => [
                    'description' => $data['description'],
                    'link1' => $data['link1'],
                    'link2' => $data['link2'],
                    'link3' => $data['link3'],
                ]
            ]);

        $this->assertDatabaseHas('user_informations', [
            'user_id' => $user->id,
            'description' => $data['description'],
        ]);
    }

    public function test_user_can_update_existing_information_successfully()
    {
        $user = User::factory()->create();
        $user->assignRole('client');
        Sanctum::actingAs($user);

        // Crear información inicial
        $initialInfo = UserInformation::factory()->create(['user_id' => $user->id]);

        $updatedData = [
            'description' => 'Descripción actualizada',
            'link1' => 'https://nuevo-link.com',
            'link2' => $initialInfo->link2, // Mantener igual
            'link3' => 'https://tercer-link-nuevo.com',
        ];

        $response = $this->postJson('/api/v1/me/user-information', $updatedData);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Información del usuario actualizada correctamente',
                'error' => false,
                'data' => [
                    'description' => $updatedData['description'],
                    'link1' => $updatedData['link1'],
                    'link2' => $updatedData['link2'],
                    'link3' => $updatedData['link3'],
                ]
            ]);

        $this->assertDatabaseHas('user_informations', [
            'user_id' => $user->id,
            'description' => $updatedData['description'],
        ]);

        // Verificar que solo existe un registro para este usuario
        $this->assertEquals(1, UserInformation::where('user_id', $user->id)->count());
    }

    public function test_user_can_view_their_information_successfully()
    {
        $user = User::factory()->create();
        $user->assignRole('client');
        Sanctum::actingAs($user);

        $info = UserInformation::factory()->create(['user_id' => $user->id]);

        $response = $this->getJson('/api/v1/me/user-information');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Información del usuario obtenida correctamente',
                'error' => false,
                'data' => [
                    'description' => $info->description,
                    'link1' => $info->link1,
                    'link2' => $info->link2,
                    'link3' => $info->link3,
                ]
            ]);
    }

    public function test_returns_empty_response_when_no_information_exists()
    {
        $user = User::factory()->create();
        $user->assignRole('client');
        Sanctum::actingAs($user);
        $response = $this->getJson('/api/v1/me/user-information');
        $response->assertStatus(404)
            ->assertJson([
                'message' => 'No se encontró información del usuario',
                'statusCode' => 404,
                'error' => false,
                'data' => null
            ]);
    }

    public function test_validation_fails_for_invalid_urls()
    {
        $user = User::factory()->create();
        $user->assignRole('client');
        Sanctum::actingAs($user);

        $invalidData = [
            'description' => 'Descripción válida',
            'link1' => 'esto-no-es-una-url',
            'link2' => 'tampoco://esto.es.valido',
            'link3' => 'http://valido.com', // Este es válido
        ];

        $response = $this->postJson('/api/v1/me/user-information', $invalidData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['link1', 'link2'])
            ->assertJsonMissingValidationErrors(['link3', 'description']);
    }

    public function test_validation_fails_for_missing_description()
    {
        $user = User::factory()->create();
        $user->assignRole('client');
        Sanctum::actingAs($user);

        $invalidData = [
            'link1' => 'https://valido.com',
            'link2' => 'https://valido.com',
            'link3' => 'https://valido.com',
        ];

        $response = $this->postJson('/api/v1/me/user-information', $invalidData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['description'])
            ->assertJsonMissingValidationErrors(['link1', 'link2', 'link3']);
    }

    public function test_validation_fails_for_description_too_long()
    {
        $user = User::factory()->create();
        $user->assignRole('client');
        Sanctum::actingAs($user);

        $invalidData = [
            'description' => str_repeat('a', 101), // 101 caracteres
            'link1' => 'https://valido.com',
        ];

        $response = $this->postJson('/api/v1/me/user-information', $invalidData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['description']);
    }

    public function test_unauthorized_access_without_authentication()
    {
        // No autenticamos al usuario

        // Test para store/update
        $response = $this->postJson('/api/v1/me/user-information', []);
        $response->assertStatus(401);

        // Test para show
        $response = $this->getJson('/api/v1/me/user-information');
        $response->assertStatus(401);
    }

    public function test_access_denied_without_email_verification()
    {
        $user = User::factory()->create(['email_verified_at' => null]);
        $user->assignRole('client');
        Sanctum::actingAs($user);

        // Test para store/update
        $response = $this->postJson('/api/v1/me/user-information', ['description' => 'test']);
        $response->assertStatus(403);

        // Test para show
        $response = $this->getJson('/api/v1/me/user-information');
        $response->assertStatus(403);
    }
}
