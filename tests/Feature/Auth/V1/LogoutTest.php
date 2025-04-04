<?php

namespace Tests\Feature\Auth\V1;

use App\Models\V1\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_logout()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->postJson('/api/v1/auth/logout', [], [
            'Authorization' => 'Bearer ' . $token
        ]);

        $response->assertStatus(200)
            ->assertJson([
                "message"=> "Logout exitoso"
            ]);

        $this->assertCount(0, $user->fresh()->tokens);
    }

    public function test_logout_revokes_all_user_tokens()
    {
        $user = User::factory()->create();
        
        // Crear múltiples tokens
        $token1 = $user->createToken('token-1')->plainTextToken;
        $token2 = $user->createToken('token-2')->plainTextToken;

        $this->postJson('/api/v1/auth/logout', [], [
            'Authorization' => 'Bearer ' . $token1
        ]);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id
        ]);
    }

   
    public function test_cannot_logout_without_authentication()
    {
        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'No Autenticado'
            ]);
    }

    public function test_invalid_token_cannot_logout()
    {
        $response = $this->postJson('/api/v1/auth/logout', [], [
            'Authorization' => 'Bearer invalid-token'
        ]);

        $response->assertStatus(401);
    }

    public function test_handles_logout_errors_gracefully()
    {
        // Mock para forzar un error
        Sanctum::actingAs(User::factory()->create());
        
        // Simulamos un error en el repositorio
        $mock = $this->mock(\App\Repository\V1\Auth\AuthRepository::class);
        $mock->shouldReceive('logout')
            ->andThrow(new \Exception('Test exception'));

        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertStatus(500)
            ->assertJson([
                'error' => true,
                'message' => 'Ha ocurrido un error: Test exception'
            ]);
    }
}