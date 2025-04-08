<?php

namespace Tests\Feature\V1\User;

use App\Events\V1\UserChangePasswordEvent;
use App\Models\V1\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ChangePasswordTest extends TestCase
{
    use RefreshDatabase;

    protected function createLocalUser($password = 'OldPassword123!')
    {
        $user = User::factory()->create([
            'email' => 'local@example.com',
            'password' => Hash::make($password),
            'registration_method' => 'local',
            'email_verified_at' => now()
        ]);

        // Crear permiso y asignarlo
        Permission::create(['name' => 'user.change-password']);
        $user->givePermissionTo('user.change-password');

        return $user;
    }

    protected function createSocialUser($provider = 'google')
    {
        return User::factory()->create([
            'email' => $provider.'@example.com',
            'password' => null,
            'registration_method' => $provider,
            'email_verified_at' => now()
        ]);
    }

    protected function getValidPayload()
    {
        return [
            'password' => 'OldPassword123!',
            'new_password' => 'NewPassword456$',
            'new_password_confirmation' => 'NewPassword456$'
        ];
    }
    protected function getValidPayloadBeforeUsed()
    {
        return [
            'password' => 'OldPasswo2rd123!',
            'new_password' => 'NewPassword456$',
            'new_password_confirmation' => 'NewPassword456$'
        ];
    }


    public function test_successful_password_change()
    {
        $user = $this->createLocalUser();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
                'Authorization' => 'Bearer '.$token,
                'Accept' => 'application/json'
            ])
            ->putJson('/api/v1/me/password', $this->getValidPayload());

        $response->assertStatus(200)
            ->assertJson([
                'error' => false,
                'message' => 'Contraseña actualizada correctamente'
            ]);

        $this->assertTrue(Hash::check('NewPassword456$', $user->fresh()->password));
    }


    public function test_fails_when_current_password_is_incorrect()
    {
        $user = $this->createLocalUser();
        $token = $user->createToken('test-token')->plainTextToken;

        $payload = $this->getValidPayload();
        $payload['password'] = 'WrongPasswo3!';

        $response = $this->withHeaders([
                'Authorization' => 'Bearer '.$token,
                'Accept' => 'application/json'
            ])
            ->putJson('/api/v1/me/password', $payload);

        $response->assertStatus(422)
            ->assertJson([
                'error' => true,
                'message' => 'Error de validación',
                'data' => [
                    'password' => ['La contraseña actual no es correcta.']
                ]
            ]);
    }


    public function test_validation_errors_for_new_password()
    {
        $user = $this->createLocalUser();
        $token = $user->createToken('test-token')->plainTextToken;

        $testCases = [
            [
                'data' => [
                    'password' => 'OldPassword123!',
                    'new_password' => 'short',
                    'new_password_confirmation' => 'short'
                ],
                'errorField' => 'new_password'
            ],
            [
                'data' => [
                    'password' => 'OldPassword123!',
                    'new_password' => 'nouppercase123!',
                    'new_password_confirmation' => 'nouppercase123!'
                ],
                'errorField' => 'new_password'
            ],
            [
                'data' => [
                    'password' => 'OldPassword123!',
                    'new_password' => 'NoSymbols123',
                    'new_password_confirmation' => 'NoSymbols123'
                ],
                'errorField' => 'new_password'
            ],
            [
                'data' => [
                    'password' => 'OldPassword123!',
                    'new_password' => 'NewPassword456$',
                    'new_password_confirmation' => 'Different456$'
                ],
                'errorField' => 'new_password'
            ]
        ];

        foreach ($testCases as $case) {
            $response = $this->withHeaders([
                    'Authorization' => 'Bearer '.$token,
                    'Accept' => 'application/json'
                ])
                ->putJson('/api/v1/me/password', $case['data']);

            $response->assertStatus(422);
        }
    }


    public function test_fails_when_new_password_was_previously_used()
    {
        $user = $this->createLocalUser();
        $token = $user->createToken('test-token')->plainTextToken;

        // Crear historial de contraseña
        $user->oldPasswords()->create([
            'password' => Hash::make('OldPassword123@')
        ]);

        $payload = $this->getValidPayloadBeforeUsed();
        $payload['new_password'] = 'OldPassword123!';
        $payload['new_password_confirmation'] = 'OldPassword123!';

        $response = $this->withHeaders([
                'Authorization' => 'Bearer '.$token,
                'Accept' => 'application/json'
            ])
            ->putJson('/api/v1/me/password', $payload);

        $response->assertStatus(422)
            ->assertJson([
                'error' => true,
                'message' => 'Error de validación',
                'data' => [
                    'new_password' => ['La contraseña debe ser diferente a contraseñas anteriores']
                ]
            ]);
    }


    public function test_fails_for_social_users()
    {
        $socialUser = $this->createSocialUser('google');
        $token = $socialUser->createToken('social-token')->plainTextToken;

        $response = $this->withHeaders([
                'Authorization' => 'Bearer '.$token,
                'Accept' => 'application/json'
            ])
            ->putJson('/api/v1/me/password', $this->getValidPayload());

        $response->assertStatus(403); // Forbidden
    }


    public function test_database_transaction_rolls_back_on_error()
    {
        $user = $this->createLocalUser();
        $token = $user->createToken('test-token')->plainTextToken;

        // Mock del repositorio para forzar error
        $mock = $this->mock(\App\Repository\V1\User\UserRepository::class, function ($mock) {
            $mock->shouldReceive('changePassword')
                ->andThrow(new \Exception('Simulated error'));
        });

        $this->app->instance(\App\Repository\V1\User\UserRepository::class, $mock);

        $response = $this->withHeaders([
                'Authorization' => 'Bearer '.$token,
                'Accept' => 'application/json'
            ])
            ->putJson('/api/v1/me/password', $this->getValidPayload());

        $response->assertStatus(500);

        // Verificar que la contraseña no cambió
        $this->assertTrue(Hash::check('OldPassword123!', $user->fresh()->password));
    }
}
