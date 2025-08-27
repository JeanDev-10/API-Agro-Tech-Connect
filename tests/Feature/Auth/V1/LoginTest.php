<?php

namespace Tests\Feature\Auth\V1;

use App\Models\V1\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_valid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('ValidPass1$'),
            'registration_method' => 'local'
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'ValidPass1$'
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'token'
                ],
            ]);
    }


    public function test_login_fails_with_invalid_password()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('ValidPass1$'),
            'registration_method' => 'local'
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpasRwo2@'
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'error' => true,
                "statusCode"=>401,
                'message' => 'Credenciales incorrectas',
                "data"=>[]
            ]);
    }


    public function test_login_fails_for_unregistered_email()
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'SomePassword1$'
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'error' => true,
                "statusCode"=>404,
                'message' => 'Usuario no registrado'
            ]);
    }


    public function test_login_fails_for_users_registered_with_social_providers()
    {
        $user = User::factory()->create([
            'email' => 'social@example.com',
            'password' => Hash::make('ValidPass1$'),
            'registration_method' => 'google' // Usuario registrado con Google
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'social@example.com',
            'password' => 'ValidPass1$'
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'error' => true,
                "statusCode"=>404,
                'message' => 'Usuario no registrado'
            ]);
    }


    public function test_email_field_is_required()
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'password' => 'SomePasrd1$'
        ]);

        $response->assertStatus(422);
        $response->assertJsonStructure([
            'data' => [
                'email',
            ]
        ]);
    }


    public function test_email_must_be_valid()
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'not-an-email',
            'password' => 'SomePasrd1$'
        ]);

        $response->assertStatus(422);
        $response->assertJsonStructure([
            'data' => [
                'email',
            ]
        ]);

    }


    public function test_password_field_is_required()
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com'
        ]);

        $response->assertStatus(422);
        $response->assertJsonStructure([
            'data' => [
                'password',
            ]
        ]);
    }


    public function test_password_must_meet_complexity_requirements()
    {
        $testCases = [
            [
                'password' => 'short',
                'error' => 'El campo password debe tener al menos 8 caracteres.'
            ],
            [
                'password' => 'nouppercase1$',
                'error' => 'El campo password debe contener al menos una mayúscula y una minúscula.'
            ],
            [
                'password' => 'NOLOWERCASE1$',
                'error' => 'El campo password debe contener al menos una mayúscula y una minúscula.'
            ],
            [
                'password' => 'NoNumbers$',
                'error' => 'El campo password debe contener al menos un número.'
            ],
            [
                'password' => 'MissingSymbol1',
                'error' => 'El campo password debe incluir al menos un carácter especial (@, &, $, %).'
            ]
        ];

        foreach ($testCases as $case) {
            $response = $this->postJson('/api/v1/auth/login', [
                'email' => 'test@example.com',
                'password' => $case['password']
            ]);

            $response->assertStatus(422);
            $response->assertJsonStructure([
                'data' => [
                    'password',
                ]
            ]);
        }
    }


    public function test_successful_login_returns_valid_token()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('ValidPass1$'),
            'registration_method' => 'local'
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'ValidPass1$'
        ]);

        $response->assertJsonStructure([
            'data' => [
                'token'
            ]
        ]);

        $token = $response->json('data.token');
        $this->assertNotNull($token);
    }


    public function test_login_response_includes_user_roles()
    {
        Role::create(['name' => 'admin']);
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('ValidPass1$'),
            'registration_method' => 'local'
        ]);

        $user->assignRole('admin');

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'ValidPass1$'
        ]);

        $response->assertStatus(200);
        $roles = $response->json('data.roles');
        $this->assertCount(1, $roles);
        $this->assertEquals('admin', $roles[0]['name']);
    }
}
