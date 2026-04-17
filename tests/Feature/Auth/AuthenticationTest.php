<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_login_screen_still_renders_when_vite_manifest_is_missing(): void
    {
        $manifestPath = public_path('build/manifest.json');
        $backupPath = public_path('build/manifest.testing.bak');

        if (File::exists($backupPath)) {
            File::delete($backupPath);
        }

        File::move($manifestPath, $backupPath);

        try {
            $response = $this->get('/login');

            $response->assertOk();
        } finally {
            if (File::exists($backupPath)) {
                File::move($backupPath, $manifestPath);
            }
        }
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_switch_account_from_login_post(): void
    {
        $currentUser = User::factory()->create([
            'email' => 'superadmin-switch@example.com',
        ]);
        $nextUser = User::factory()->create([
            'email' => 'conductor-switch@example.com',
        ]);

        $response = $this->actingAs($currentUser)->post('/login', [
            'email' => $nextUser->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($nextUser);
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_manager_does_not_get_redirected_to_stale_driver_intended_url(): void
    {
        $manager = User::factory()->create([
            'email' => 'manager-login@example.com',
            'is_super_admin' => false,
        ]);
        $manager->syncRoles(['manager']);

        $response = $this
            ->withSession(['url.intended' => route('driver.dashboard', absolute: false)])
            ->post('/login', [
                'email' => $manager->email,
                'password' => 'password',
            ]);

        $this->assertAuthenticatedAs($manager);
        $response->assertRedirect(route('dashboard', absolute: false));
    }
}
