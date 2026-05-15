<?php

namespace Tests\Feature;

use App\Models\Apartment;
use App\Models\Building;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantAdminPropertyCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_tenant_admin_can_manage_buildings_and_apartments_within_own_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->forTenant($tenant)->create();
        $user->roles()->attach($this->roleId('tenant_owner'));

        $this->actingAs($user)
            ->post(route('admin.buildings.store'), [
                'name' => 'Palm Tower',
                'slug' => 'palm-tower',
                'status' => 'active',
                'city' => 'Cairo',
            ])->assertRedirect();

        $building = Building::query()->where('slug', 'palm-tower')->firstOrFail();

        $this->actingAs($user)
            ->post(route('admin.apartments.store'), [
                'building_id' => $building->id,
                'unit_number' => 'A-101',
                'occupancy_status' => 'vacant',
                'status' => 'active',
                'unit_type' => 'flat',
                'bedrooms' => 3,
                'bathrooms' => 2,
                'area_value' => 120.5,
                'area_unit' => 'sqm',
            ])->assertRedirect();

        $apartment = Apartment::query()->where('unit_number', 'A-101')->firstOrFail();

        $this->actingAs($user)
            ->put(route('admin.buildings.update', $building), [
                'name' => 'Palm Tower Updated',
                'slug' => 'palm-tower',
                'status' => 'active',
                'city' => 'Giza',
            ])->assertRedirect(route('admin.buildings.show', $building));

        $this->actingAs($user)
            ->put(route('admin.apartments.update', $apartment), [
                'building_id' => $building->id,
                'unit_number' => 'A-101',
                'occupancy_status' => 'occupied',
                'status' => 'active',
                'unit_type' => 'duplex',
                'bedrooms' => 4,
                'bathrooms' => 3,
                'area_value' => 160,
                'area_unit' => 'sqm',
            ])->assertRedirect(route('admin.apartments.show', $apartment));

        $this->assertDatabaseHas('buildings', [
            'id' => $building->id,
            'tenant_id' => $tenant->id,
            'name' => 'Palm Tower Updated',
            'city' => 'Giza',
        ]);

        $this->assertDatabaseHas('apartments', [
            'id' => $apartment->id,
            'tenant_id' => $tenant->id,
            'occupancy_status' => 'occupied',
            'unit_type' => 'duplex',
        ]);
    }

    public function test_tenant_admin_cannot_access_other_tenant_buildings_or_apartments(): void
    {
        $tenant = Tenant::factory()->create();
        $otherTenant = Tenant::factory()->create();
        $user = User::factory()->forTenant($tenant)->create();
        $user->roles()->attach($this->roleId('tenant_owner'));

        $foreignBuilding = Building::factory()->create([
            'tenant_id' => $otherTenant->id,
        ]);
        $foreignApartment = Apartment::factory()->forBuilding($foreignBuilding)->create([
            'tenant_id' => $otherTenant->id,
        ]);

        $this->actingAs($user)
            ->get(route('admin.buildings.show', $foreignBuilding))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('admin.apartments.show', $foreignApartment))
            ->assertForbidden();
    }

    private function roleId(string $slug): int
    {
        return (int) Role::query()->where('slug', $slug)->value('id');
    }
}
