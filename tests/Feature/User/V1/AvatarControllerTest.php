<?php

namespace Tests\Feature\V1\User;

use App\Models\V1\Image;
use App\Models\V1\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AvatarControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $localUser;
    protected User $socialUser;
    protected string $tokenLocalUser;
    protected string $tokenSocialUser;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        // Crear usuarios de prueba
        $this->localUser = User::factory()->create([
            'registration_method' => 'local',
            'email_verified_at' => now()
        ]);
        Role::create(['name' => 'client_social']);
        Permission::firstOrCreate(["name"=>"user.upload-avatar"]);
        $client_role=Role::create(['name' => 'client']);
        $client_role->syncPermissions(['user.upload-avatar']);
        $this->localUser->assignRole('client');

        $this->socialUser = User::factory()->create([
            'registration_method' => 'google',
            'email_verified_at' => now()
        ]);

        $this->socialUser->assignRole('client_social');

        // Generar tokens
        $this->tokenLocalUser = $this->localUser->createToken('test-token')->plainTextToken;
        $this->tokenSocialUser = $this->socialUser->createToken('test-token')->plainTextToken;
    }


    public function test_avatar_created_successfully_for_local_user()
    {
        $file = UploadedFile::fake()->image('avatar.jpg');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenLocalUser,
        ])->postJson('/api/v1/me/avatar', [
            'avatar' => $file
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'statusCode',
                'error',
                'data' => [
                    'avatar_url'
                ]
            ])
            ->assertJson([
                'message' => 'Avatar actualizado correctamente',
                'error' => false
            ]);

        // Verify it was created in the database
        $this->assertNotNull($this->localUser->fresh()->image);

        // Verify the file was saved
        Storage::disk('public')->assertExists($this->localUser->image->image_Uuid);
    }


    public function test_avatar_updated_successfully_for_local_user()
    {
        // First create an existing avatar
        $oldFile = UploadedFile::fake()->image('old-avatar.jpg');
        $this->localUser->image()->create([
            'image_Uuid' => 'avatars/old.jpg',
            'url' => 'http://example.com/old.jpg'
        ]);
        Storage::disk('public')->put('avatars/old.jpg', $oldFile->get());

        $newFile = UploadedFile::fake()->image('new-avatar.jpg');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenLocalUser,
        ])->postJson('/api/v1/me/avatar', [
            'avatar' => $newFile
        ]);

        $response->assertStatus(200);

        // Verify the old file was deleted
        Storage::disk('public')->assertMissing('avatars/old.jpg');

        // Verify the new file exists
        Storage::disk('public')->assertExists($this->localUser->fresh()->image->image_Uuid);
    }


    public function test_error_not_allowed_for_social_accounts()
    {
        $file = UploadedFile::fake()->image('avatar.jpg');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenSocialUser,
        ])->postJson('/api/v1/me/avatar', [
            'avatar' => $file
        ]);

        $response->assertStatus(403);

    }


    public function test_validation_errors()
    {
        // Case 1: File not sent
        $response1 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenLocalUser,
        ])->postJson('/api/v1/me/avatar');

        $response1->assertStatus(422)
            ->assertJsonValidationErrors(['avatar']);

        // Case 2: File is not an image
        $notImage = UploadedFile::fake()->create('document.pdf', 1000);

        $response2 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenLocalUser,
        ])->postJson('/api/v1/me/avatar', [
            'avatar' => $notImage
        ]);

        $response2->assertStatus(422)
            ->assertJsonValidationErrors(['avatar']);

        // Case 3: File too large
        $largeImage = UploadedFile::fake()->image('large.jpg')->size(4000); // 4MB

        $response3 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenLocalUser,
        ])->postJson('/api/v1/me/avatar', [
            'avatar' => $largeImage
        ]);

        $response3->assertStatus(422)
            ->assertJsonValidationErrors(['avatar']);
    }


    public function test_avatar_deleted_successfully()
    {
        // Create an existing avatar
        $file = UploadedFile::fake()->image('avatar.jpg');
        $image = $this->localUser->image()->create([
            'image_Uuid' => 'avatars/test.jpg',
            'url' => 'http://example.com/test.jpg'
        ]);
        Storage::disk('public')->put('avatars/test.jpg', $file->get());

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenLocalUser,
        ])->deleteJson('/api/v1/me/avatar');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Avatar eliminado correctamente',
                'error' => false
            ]);

        // Verify it was deleted from the database
        $this->assertNull($this->localUser->fresh()->image);

        // Verify the physical file was deleted
        Storage::disk('public')->assertMissing('avatars/test.jpg');
    }


    public function test_avatar_error_nothing_to_delete()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenLocalUser,
        ])->deleteJson('/api/v1/me/avatar');

        $response->assertStatus(404)
            ->assertJson([
                'error' => true,
                'message' => 'El usuario no tiene un avatar para eliminar'
            ]);
    }


    public function test_error_deleting_avatar_for_social_accounts()
    {
        // Create an existing avatar for the social user
        $file = UploadedFile::fake()->image('avatar.jpg');
        $this->socialUser->image()->create([
            'image_Uuid' => 'avatars/social.jpg',
            'url' => 'http://example.com/social.jpg'
        ]);
        Storage::disk('public')->put('avatars/social.jpg', $file->get());

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenSocialUser,
        ])->deleteJson('/api/v1/me/avatar');

        $response->assertStatus(403)
            ->assertJson([
                'error' => true,
            ]);
    }



}
