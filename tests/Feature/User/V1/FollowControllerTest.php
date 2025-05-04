<?php

namespace Tests\Feature\V1\User;

use App\Models\V1\Follow;
use App\Models\V1\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FollowControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $admin;
    protected User $userToFollow;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            "id"=>1
        ]);
        // Create a regular user
        $this->user = User::factory()->create();

        // Create another user to follow
        $this->userToFollow = User::factory()->create();

        // Create admin user


        // Generate Sanctum token
        $this->token = $this->user->createToken('test-token')->plainTextToken;
    }

    public function test_follow_user_successfully(): void
    {
        $encryptedId = Crypt::encrypt($this->userToFollow->id);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/v1/users/follow', [
            'user_id' => $encryptedId,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Ahora estás siguiendo a este usuario',
                'error' => false,
            ])
            ->assertJsonStructure([
                'data' => ['follow_id']
            ]);

        $this->assertDatabaseHas('follows', [
            'follower_id' => $this->user->id,
            'followed_id' => $this->userToFollow->id,
        ]);
    }


    public function test_follow_fails_with_invalid_encrypted_user_id(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/v1/users/follow', [
            'user_id' => 'invalid-encrypted-id',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Error de validación',
                'error' => true,
            ]);
    }

    public function test_follow_fails_when_user_does_not_exist(): void
    {
        $nonExistentUserId = 9999;
        $encryptedId = Crypt::encrypt($nonExistentUserId);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/v1/users/follow', [
            'user_id' => $encryptedId,
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'El usuario no existe',
                'error' => true,
            ]);
    }

    public function test_follow_fails_when_already_following(): void
    {
        // First follow
        Follow::create([
            'follower_id' => $this->user->id,
            'followed_id' => $this->userToFollow->id,
        ]);

        $encryptedId = Crypt::encrypt($this->userToFollow->id);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/v1/users/follow', [
            'user_id' => $encryptedId,
        ]);

        $response->assertStatus(409)
            ->assertJson([
                'message' => 'Ya estás siguiendo a este usuario.',
                'error' => true,
            ]);
    }

    public function test_follow_fails_when_trying_to_follow_self(): void
    {
        $encryptedId = Crypt::encrypt($this->user->id);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/v1/users/follow', [
            'user_id' => $encryptedId,
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'No puedes seguirte a ti mismo.',
                'error' => true,
            ]);
    }

    public function test_follow_fails_when_trying_to_follow_admin(): void
    {
        $encryptedId = Crypt::encrypt($this->admin->id);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/v1/users/follow', [
            'user_id' => $encryptedId,
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'No puedes seguir al administrador.',
                'error' => true,
            ]);
    }

    public function test_unfollow_user_successfully(): void
    {
        // First follow the user
        Follow::create([
            'follower_id' => $this->user->id,
            'followed_id' => $this->userToFollow->id,
        ]);

        $encryptedId = Crypt::encrypt($this->userToFollow->id);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->deleteJson('/api/v1/users/unfollow', [
            'user_id' => $encryptedId,
        ]);
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Has dejado de seguir a este usuario',
                'error' => false,
            ]);

        $this->assertDatabaseMissing('follows', [
            'follower_id' => $this->user->id,
            'followed_id' => $this->userToFollow->id,
        ]);
    }

    public function test_unfollow_fails_with_invalid_encrypted_user_id(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->deleteJson('/api/v1/users/unfollow', [
            'user_id' => 'invalid-encrypted-id',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Error de validación',
                'error' => true,
            ]);
    }

    public function test_unfollow_fails_when_user_does_not_exist(): void
    {
        $nonExistentUserId = 9999;
        $encryptedId = Crypt::encrypt($nonExistentUserId);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->deleteJson('/api/v1/users/unfollow', [
            'user_id' => $encryptedId,
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'El usuario no existe',
                'error' => true,
            ]);
    }

    public function test_unfollow_fails_when_not_following(): void
    {
        $encryptedId = Crypt::encrypt($this->userToFollow->id);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->deleteJson('/api/v1/users/unfollow', [
            'user_id' => $encryptedId,
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'No estás siguiendo a este usuario.',
                'error' => true,
            ]);
    }

    public function test_follow_endpoint_requires_authentication(): void
    {
        $encryptedId = Crypt::encrypt($this->userToFollow->id);

        $response = $this->postJson('/api/v1/users/follow', [
            'user_id' => $encryptedId,
        ]);

        $response->assertStatus(401);
    }

    public function test_unfollow_endpoint_requires_authentication(): void
    {
        $encryptedId = Crypt::encrypt($this->userToFollow->id);

        $response = $this->deleteJson('/api/v1/users/unfollow', [
            'user_id' => $encryptedId,
        ]);

        $response->assertStatus(401);
    }
}
