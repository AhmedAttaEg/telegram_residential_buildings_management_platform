<?php

namespace Tests\Feature;

use App\Models\Apartment;
use App\Models\Building;
use App\Models\Tenant;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BuildingApartmentDomainTest extends TestCase
{
    use RefreshDatabase;

    public function test_buildings_and_apartments_tables_support_tenant_isolation_and_accounting_linkage(): void
    {
        $this->assertTrue(Schema::hasTable('buildings'));
        $this->assertTrue(Schema::hasColumns('buildings', [
            'tenant_id',
            'name',
            'slug',
            'status',
            'country',
            'city',
            'area',
            'address_line_1',
            'address_line_2',
            'postal_code',
        ]));

        $this->assertTrue(Schema::hasTable('apartments'));
        $this->assertTrue(Schema::hasColumns('apartments', [
            'tenant_id',
            'building_id',
            'unit_number',
            'occupancy_status',
            'status',
            'floor_number',
            'unit_type',
            'bedrooms',
            'bathrooms',
            'area_value',
            'area_unit',
        ]));

        $this->assertFalse(Schema::hasColumn('apartments', 'balance'));
        $this->assertFalse(Schema::hasColumn('apartments', 'debt'));
    }

    public function test_building_and_apartment_relationships_scopes_and_accessors_work(): void
    {
        $tenant = Tenant::factory()->create();
        $otherTenant = Tenant::factory()->create();

        $building = Building::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Palm Tower',
            'address_line_1' => '123 Nile Street',
            'address_line_2' => null,
            'area' => null,
            'city' => 'Cairo',
            'country' => 'Egypt',
            'postal_code' => null,
        ]);

        $apartment = Apartment::factory()->forBuilding($building)->occupied()->create([
            'tenant_id' => $tenant->id,
            'unit_number' => 'A-101',
        ]);

        Building::factory()->create([
            'tenant_id' => $otherTenant->id,
            'status' => 'inactive',
        ]);

        Apartment::factory()->create();

        $this->assertCount(1, $tenant->buildings);
        $this->assertCount(1, $tenant->apartments);
        $this->assertTrue($building->tenant->is($tenant));
        $this->assertTrue($apartment->building->is($building));
        $this->assertTrue($apartment->tenant->is($tenant));

        $this->assertCount(1, Building::forTenant($tenant)->active()->get());
        $this->assertCount(1, Apartment::forTenant($tenant)->occupied()->get());
        $this->assertCount(0, Apartment::forTenant($tenant)->vacant()->get());

        $this->assertSame('123 Nile Street, Cairo, Egypt', $building->full_address);
        $this->assertSame('Palm Tower - Unit A-101', $apartment->load('building')->display_label);
    }

    public function test_tenant_scoped_building_slug_uniqueness_is_enforced(): void
    {
        $tenant = Tenant::factory()->create();
        Building::factory()->create([
            'tenant_id' => $tenant->id,
            'slug' => 'central-park',
        ]);

        $this->expectException(QueryException::class);

        Building::factory()->create([
            'tenant_id' => $tenant->id,
            'slug' => 'central-park',
        ]);

    }

    public function test_apartment_unit_number_uniqueness_is_enforced_within_a_building(): void
    {
        $tenant = Tenant::factory()->create();
        $building = Building::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        Apartment::factory()->forBuilding($building)->create([
            'tenant_id' => $tenant->id,
            'unit_number' => '101',
        ]);

        $this->expectException(QueryException::class);

        Apartment::factory()->forBuilding($building)->create([
            'tenant_id' => $tenant->id,
            'unit_number' => '101',
        ]);
    }
}
