<?php

namespace Tests\Feature\Admin\V1;

use App\Events\V1\UserDeletedAccountAdmintEvent;
use App\Models\V1\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Event;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DeleteAccountAdminTest extends TestCase
{
    use RefreshDatabase;

    private $admin;
    private $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        Event::fake();

        // Crear permisos primero
        Permission::firstOrCreate(['name' => 'admin.delete-account']);


        // Crear rol y asignar permisos
        $roleAdmin = Role::firstOrCreate(['name' => 'admin']);
        $roleAdmin->givePermissionTo('admin.delete-account');

        // Crear usuarios
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->regularUser = User::factory()->create();
    }


    public function test_admin_with_permission_can_delete_user()
    {
        $userToDelete = User::factory()->create();
        $encryptedId = Crypt::encrypt($userToDelete->id);

        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/v1/users/{$encryptedId}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('users', ['id' => $userToDelete->id]);
    }


    public function test_regular_user_cannot_delete_account()
    {
        $userToDelete = User::factory()->create();
        $encryptedId = Crypt::encrypt($userToDelete->id);

        $response = $this->actingAs($this->regularUser)
            ->deleteJson("/api/v1/users/{$encryptedId}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('users', ['id' => $userToDelete->id]);
    }


    public function test_cannot_delete_nonexistent_user()
    {
        $nonExistentId = 9999;
        $encryptedId = Crypt::encrypt($nonExistentId);

        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/v1/users/{$encryptedId}");

        $response->assertStatus(404)
            ->assertJson(['message' => 'Usuario no existe']);
    }


    public function test_cannot_delete_user_with_invalid_id()
    {
        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/v1/users/invalid-id");

        $response->assertStatus(500);
    }


    public function test_unauthenticated_user_cannot_delete_account()
    {
        $userToDelete = User::factory()->create();
        $encryptedId = Crypt::encrypt($userToDelete->id);

        $response = $this->deleteJson("/api/v1/users/{$encryptedId}");

        $response->assertStatus(401);
        $this->assertDatabaseHas('users', ['id' => $userToDelete->id]);
    }
    public function test_admin_cannot_delete_own_account()
    {
        $encryptedId = Crypt::encrypt($this->admin->id);

        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/v1/users/{$encryptedId}");

        $response->assertStatus(400);
        $this->assertDatabaseHas('users', ['id' => $this->admin->id]);
    }
}
