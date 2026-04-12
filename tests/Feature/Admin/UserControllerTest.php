<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Route;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class UserControllerTest extends TestCase
{
    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create(['is_super_admin' => true]);
        $this->adminUser->givePermissionTo([
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',
        ]);
    }

    // ──────────────────────────────────────────────────────────────
    // INDEX
    // ──────────────────────────────────────────────────────────────

    public function test_admin_can_view_users_list(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('admin.users.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.users.index');
    }

    public function test_user_without_permission_cannot_view_list(): void
    {
        $user = User::factory()->create(['is_super_admin' => false]);

        $response = $this->actingAs($user)->get(route('admin.users.index'));

        $response->assertStatus(403);
    }

    public function test_can_search_users_by_name(): void
    {
        User::factory()->create(['name' => 'Carlos Rodríguez', 'is_super_admin' => true]);
        User::factory()->create(['name' => 'María López', 'is_super_admin' => true]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.users.index', ['search' => 'Carlos']));

        $response->assertSee('Carlos Rodríguez');
        $response->assertDontSee('María López');
    }

    public function test_can_search_users_by_email(): void
    {
        User::factory()->create(['email' => 'carlos@empresa.com', 'is_super_admin' => true]);
        User::factory()->create(['email' => 'otro@empresa.com', 'is_super_admin' => true]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.users.index', ['search' => 'carlos@empresa']));

        $response->assertSee('carlos@empresa.com');
    }

    public function test_can_filter_users_by_role(): void
    {
        $conductor = User::factory()->create(['is_super_admin' => false]);
        $conductor->syncRoles(['conductor']);

        $admin = User::factory()->create(['is_super_admin' => false]);
        $admin->syncRoles(['admin']);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.users.index', ['role' => 'conductor']));

        $response->assertSee($conductor->name);
        $response->assertDontSee($admin->name);
    }

    // ──────────────────────────────────────────────────────────────
    // CREATE
    // ──────────────────────────────────────────────────────────────

    public function test_admin_can_access_create_form(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('admin.users.create'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.users.create');
    }

    // ──────────────────────────────────────────────────────────────
    // STORE
    // ──────────────────────────────────────────────────────────────

    public function test_admin_can_create_user(): void
    {
        $response = $this->actingAs($this->adminUser)->post(route('admin.users.store'), [
            'name'                  => 'Nuevo Usuario',
            'email'                 => 'nuevo@test.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'role'                  => 'admin',
            'is_active'             => true,
            'must_change_password'  => true,
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('users', ['email' => 'nuevo@test.com']);
    }

    public function test_conductor_user_requires_route_id(): void
    {
        $response = $this->actingAs($this->adminUser)->post(route('admin.users.store'), [
            'name'                  => 'Conductor Sin Ruta',
            'email'                 => 'conductor@test.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'role'                  => 'conductor',
            // Sin route_id
        ]);

        $response->assertSessionHasErrors('route_id');
    }

    public function test_conductor_user_is_created_with_route(): void
    {
        $route = Route::factory()->create();

        $response = $this->actingAs($this->adminUser)->post(route('admin.users.store'), [
            'name'                  => 'Conductor Con Ruta',
            'email'                 => 'conductor2@test.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'role'                  => 'conductor',
            'route_id'              => $route->id,
            'is_active'             => true,
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', [
            'email'    => 'conductor2@test.com',
            'route_id' => $route->id,
        ]);
    }

    public function test_non_conductor_user_does_not_get_route_id(): void
    {
        $route = Route::factory()->create();

        $this->actingAs($this->adminUser)->post(route('admin.users.store'), [
            'name'                  => 'Admin User',
            'email'                 => 'adminuser@test.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'role'                  => 'admin',
            'route_id'              => $route->id, // enviado pero no debe guardarse
        ]);

        $this->assertDatabaseHas('users', [
            'email'    => 'adminuser@test.com',
            'route_id' => null,
        ]);
    }

    public function test_duplicate_email_is_rejected(): void
    {
        User::factory()->create(['email' => 'duplicado@test.com', 'is_super_admin' => true]);

        $response = $this->actingAs($this->adminUser)->post(route('admin.users.store'), [
            'name'                  => 'Otro',
            'email'                 => 'duplicado@test.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'role'                  => 'admin',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_password_confirmation_mismatch_is_rejected(): void
    {
        $response = $this->actingAs($this->adminUser)->post(route('admin.users.store'), [
            'name'                  => 'Test User',
            'email'                 => 'test@test.com',
            'password'              => 'password123',
            'password_confirmation' => 'diferente',
            'role'                  => 'admin',
        ]);

        $response->assertSessionHasErrors('password');
    }

    // ──────────────────────────────────────────────────────────────
    // UPDATE
    // ──────────────────────────────────────────────────────────────

    public function test_admin_can_update_user(): void
    {
        $user = User::factory()->create(['name' => 'Original', 'is_super_admin' => true]);
        $user->syncRoles(['admin']);

        $response = $this->actingAs($this->adminUser)->put(route('admin.users.update', $user), [
            'name'  => 'Actualizado',
            'email' => $user->email,
            'role'  => 'admin',
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Actualizado']);
    }

    public function test_super_admin_cannot_remove_own_super_admin_role(): void
    {
        $superAdmin = User::factory()->create(['is_super_admin' => true]);
        $superAdmin->syncRoles(['super_admin']);

        $response = $this->actingAs($superAdmin)->put(route('admin.users.update', $superAdmin), [
            'name'  => $superAdmin->name,
            'email' => $superAdmin->email,
            'role'  => 'admin', // intenta quitarse super_admin
        ]);

        $response->assertSessionHas('error');
        $this->assertTrue($superAdmin->fresh()->hasRole('super_admin'));
    }

    public function test_password_is_updated_when_provided(): void
    {
        $user = User::factory()->create(['is_super_admin' => true]);
        $user->syncRoles(['admin']);
        $oldHash = $user->password;

        $this->actingAs($this->adminUser)->put(route('admin.users.update', $user), [
            'name'                  => $user->name,
            'email'                 => $user->email,
            'role'                  => 'admin',
            'password'              => 'nuevaPassword123',
            'password_confirmation' => 'nuevaPassword123',
        ]);

        $this->assertNotSame($oldHash, $user->fresh()->password);
    }

    public function test_password_is_not_changed_when_not_provided(): void
    {
        $user = User::factory()->create(['is_super_admin' => true]);
        $user->syncRoles(['admin']);
        $oldHash = $user->password;

        $this->actingAs($this->adminUser)->put(route('admin.users.update', $user), [
            'name'     => $user->name,
            'email'    => $user->email,
            'role'     => 'admin',
            'password' => '', // vacío
        ]);

        $this->assertSame($oldHash, $user->fresh()->password);
    }

    // ──────────────────────────────────────────────────────────────
    // DESTROY (soft-disable)
    // ──────────────────────────────────────────────────────────────

    public function test_admin_can_deactivate_user(): void
    {
        $user = User::factory()->create(['is_active' => true, 'is_super_admin' => true]);

        $response = $this->actingAs($this->adminUser)->delete(route('admin.users.destroy', $user));

        $response->assertRedirect(route('admin.users.index'));
        $this->assertFalse($user->fresh()->is_active);
        $this->assertDatabaseHas('users', ['id' => $user->id]); // no borra de BD
    }

    public function test_user_cannot_deactivate_themselves(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->delete(route('admin.users.destroy', $this->adminUser));

        $response->assertSessionHas('error');
        $this->assertTrue($this->adminUser->fresh()->is_active);
    }

    // ──────────────────────────────────────────────────────────────
    // TOGGLE
    // ──────────────────────────────────────────────────────────────

    public function test_admin_can_toggle_user_active_status(): void
    {
        $user = User::factory()->create(['is_active' => true, 'is_super_admin' => true]);

        $this->actingAs($this->adminUser)->post(route('admin.users.toggle', $user));

        $this->assertFalse($user->fresh()->is_active);

        $this->actingAs($this->adminUser)->post(route('admin.users.toggle', $user));

        $this->assertTrue($user->fresh()->is_active);
    }

    public function test_user_cannot_toggle_themselves(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.users.toggle', $this->adminUser));

        $response->assertSessionHas('error');
    }

    // ──────────────────────────────────────────────────────────────
    // RESET PASSWORD
    // ──────────────────────────────────────────────────────────────

    public function test_admin_can_reset_user_password(): void
    {
        $user = User::factory()->create(['is_super_admin' => true]);
        $user->syncRoles(['admin']);
        $oldHash = $user->password;

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.users.reset-password', $user));

        $response->assertSessionHas('success');
        $this->assertNotSame($oldHash, $user->fresh()->password);
        $this->assertTrue($user->fresh()->must_change_password);
    }

    public function test_reset_password_session_contains_temporary_password(): void
    {
        $user = User::factory()->create(['is_super_admin' => true]);
        $user->syncRoles(['admin']);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.users.reset-password', $user));

        $successMessage = $response->getSession()->get('success');
        $this->assertStringContainsString('contraseña temporal', $successMessage);
    }

    public function test_temporary_password_is_12_chars_hex(): void
    {
        $user = User::factory()->create(['is_super_admin' => true]);
        $user->syncRoles(['admin']);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.users.reset-password', $user));

        $successMessage = $response->getSession()->get('success');
        // Extraer la contraseña temporal del mensaje
        preg_match('/contraseña temporal es: ([a-f0-9]+)/i', $successMessage, $matches);

        $this->assertNotEmpty($matches);
        $this->assertSame(12, strlen($matches[1]));
        $this->assertMatchesRegularExpression('/^[a-f0-9]+$/', $matches[1]);
    }
}
