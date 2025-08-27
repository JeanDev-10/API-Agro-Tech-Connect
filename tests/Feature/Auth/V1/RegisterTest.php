<?php

namespace Tests\Feature\Auth\V1;

use App\Models\V1\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_with_valid_data()
    {
        Role::create(['name' => 'client']);
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'John',
            'lastname' => 'Doe',
            'username' => 'johndoe',
            'email' => 'john@example.com',
            'password' => 'ValidPass1$',
            'password_confirmation' => 'ValidPass1$',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'username' => 'johndoe'
        ]);
    }

    public function test_name_field_is_required()
    {
        $response = $this->postJson('/api/v1/auth/register', [
            // Missing name
            'lastname' => 'Doe',
            'username' => 'johndoe',
            'email' => 'john@example.com',
            'password' => 'ValidPass1$',
            'password_confirmation' => 'ValidPass1$',
        ]);
        $response->assertStatus(422);
        $response->assertJsonStructure([
            'data' => [
                'name',
            ]
        ]);
    }

    public function test_name_must_be_between_3_and_20_characters()
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Jo',
            'lastname' => 'Doe',
            'username' => 'johndoe',
            'email' => 'john@example.com',
            'password' => 'ValidPass1$',
            'password_confirmation' => 'ValidPass1$',
        ]);

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => str_repeat('a', 21), // Too long
            // ... other valid fields
        ]);
        $response->assertStatus(422);
        $response->assertJsonStructure([
            'data' => [
                'name',
            ]
        ]);
    }

    public function test_username_must_be_unique()
    {
        User::factory()->create(['username' => 'takenusername']);

        $response = $this->postJson('/api/v1/auth/register', [
            'username' => 'takenusername',
            'lastname' => 'Doe',
            'email' => 'john@example.com',
            'password' => 'ValidPass1$',
            'password_confirmation' => 'ValidPass1$',
            // ... other valid fields
        ]);
        $response->assertStatus(422);
        $response->assertJsonStructure([
            'data' => [
                'username',
            ]
        ]);
    }

    public function test_email_must_be_valid_and_unique()
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->postJson('/api/v1/auth/register', [
            'email' => 'not-an-email',
            'username' => 'takenusername',
            'lastname' => 'Doe',
            'password' => 'ValidPass1$',
            'password_confirmation' => 'ValidPass1$',
            // ... other valid fields
        ]);

        $response = $this->postJson('/api/v1/auth/register', [
            'email' => 'existing@example.com',
            // ... other valid fields
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
                'error' => 'password'
            ],
            [
                'password' => 'nouppercase1$',
                'error' => 'password'
            ],
            [
                'password' => 'NOLOWERCASE1$',
                'error' => 'password'
            ],
            [
                'password' => 'NoNumbers$',
                'error' => 'password'
            ],
            [
                'password' => 'MissingSymbol1',
                'error' => 'password'
            ],
            [
                'password' => 'TooLongPassword123456$',
                'error' => 'password'
            ]
        ];

        foreach ($testCases as $case) {
            $response = $this->postJson('/api/v1/auth/register', [
                'password' => $case['password'],
                'password_confirmation' => $case['password'],
                'email' => 'not-an-email',
                'username' => 'takenusername',
                'lastname' => 'Doe',

                // ... other valid fields
            ]);
            $response->assertStatus(422);
            $response->assertJsonStructure([
                'data' => [
                    'password',
                ]
            ]);
        }
    }

    public function test_password_must_match_confirmation()
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'password' => 'ValidPass1$',
            'password_confirmation' => 'Different123$',
            'email' => 'not-an-email',
            'username' => 'takenusername',
            'lastname' => 'Doe',
           
            // ... other valid fields
        ]);
        $response->assertStatus(422);
        $response->assertJsonStructure([
            'data' => [
                'password',
            ]
        ]);
    }


    public function test_password_is_hashed_in_database()
    {
        $plainPassword = 'SecurePass1$';

        $this->postJson('/api/v1/auth/register', [
            'name' => 'John',
            'lastname' => 'Doe',
            'username' => 'johndoe',
            'email' => 'john@example.com',
            'password' => $plainPassword,
            'password_confirmation' => $plainPassword,
        ]);

        $user = User::first();
        $this->assertTrue(Hash::check($plainPassword, $user->password));
        $this->assertNotEquals($plainPassword, $user->password);
    }
}
