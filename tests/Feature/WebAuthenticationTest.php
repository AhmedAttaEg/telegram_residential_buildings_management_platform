<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_guest_can_view_login_page(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee(__('web.auth.title'));
    }

    public function test_platform_owner_login_redirects_to_owner_dashboard(): void
    {
        $user = User::factory()->create([
            'email' => 'owner@example.com',
        ]);
        $user->roles()->attach($this->roleId('platform_owner'));

        $this->post(route('login.store'), [
            'email' => 'owner@example.com',
            'password' => 'password',
        ])->assertRedirect(route('owner.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_tenant_staff_login_redirects_to_admin_dashboard(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->forTenant($tenant)->create([
            'email' => 'admin@example.com',
        ]);
        $user->roles()->attach($this->roleId('tenant_owner'));

        $this->post(route('login.store'), [
            'email' => 'admin@example.com',
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_resident_login_redirects_to_resident_dashboard(): void
    {
        $tenant = Tenant::factory()->create();
        $resident = \App\Models\Resident::factory()->create([
            'tenant_id' => $tenant->id,
        ]);
        $user = User::factory()->forResident($resident)->create([
            'email' => 'resident@example.com',
        ]);
        $user->roles()->attach($this->roleId('resident'));

        $this->post(route('login.store'), [
            'email' => 'resident@example.com',
            'password' => 'password',
        ])->assertRedirect(route('resident.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_inactive_user_cannot_log_in_through_web_form(): void
    {
        $user = User::factory()->inactive()->create([
            'email' => 'inactive-web@example.com',
        ]);
        $user->roles()->attach($this->roleId('resident'));

        $this->from(route('login'))
            ->post(route('login.store'), [
                'email' => 'inactive-web@example.com',
                'password' => 'password',
            ])->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_authenticated_user_can_switch_locale_and_persist_preference(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->forTenant($tenant)->create([
            'preferred_locale' => 'ar',
        ]);
        $user->roles()->attach($this->roleId('tenant_owner'));

        $this->actingAs($user)
            ->from(route('admin.dashboard'))
            ->post(route('locale.update'), [
                'locale' => 'en',
            ])->assertRedirect(route('admin.dashboard'));

        $this->assertSame('en', session('locale'));
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'preferred_locale' => 'en',
        ]);
    }

    public function test_authenticated_user_can_log_out(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->forTenant($tenant)->create();
        $user->roles()->attach($this->roleId('tenant_owner'));

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_resident_cannot_access_tenant_admin_dashboard(): void
    {
        $tenant = Tenant::factory()->create();
        $resident = \App\Models\Resident::factory()->create([
            'tenant_id' => $tenant->id,
        ]);
        $user = User::factory()->forResident($resident)->create();
        $user->roles()->attach($this->roleId('resident'));

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    private function roleId(string $slug): int
    {
        return (int) Role::query()->where('slug', $slug)->value('id');
    }
}
