<?php

namespace Tests\Feature;

use App\Models\Apartment;
use App\Models\Building;
use App\Models\Resident;
use App\Models\Tenant;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ResidentDomainTest extends TestCase
{
    use RefreshDatabase;

    public function test_residents_and_occupancy_tables_support_ownership_and_contact_tracking(): void
    {
        $this->assertTrue(Schema::hasTable('residents'));
        $this->assertTrue(Schema::hasColumns('residents', [
            'tenant_id',
            'first_name',
            'last_name',
            'resident_type',
            'status',
            'is_primary_owner',
            'phone',
            'secondary_phone',
            'email',
            'emergency_contact_name',
            'emergency_contact_phone',
            'notes',
        ]));

        $this->assertTrue(Schema::hasTable('apartment_residents'));
        $this->assertTrue(Schema::hasColumns('apartment_residents', [
            'tenant_id',
            'apartment_id',
            'resident_id',
            'tenancy_type',
            'occupancy_status',
            'ownership_percentage',
            'move_in_at',
            'move_out_at',
            'is_primary_contact',
        ]));
    }

    public function test_resident_relationships_scopes_and_accessors_work_with_occupancy_metadata(): void
    {
        $tenant = Tenant::factory()->create();
        $otherTenant = Tenant::factory()->create();
        $building = Building::factory()->create([
            'tenant_id' => $tenant->id,
        ]);
        $apartment = Apartment::factory()->forBuilding($building)->create([
            'tenant_id' => $tenant->id,
            'unit_number' => 'B-201',
        ]);

        $resident = Resident::factory()->owner()->create([
            'tenant_id' => $tenant->id,
            'first_name' => 'Fatma',
            'last_name' => 'Ali',
        ]);

        Resident::factory()->create([
            'tenant_id' => $otherTenant->id,
            'resident_type' => 'resident_viewer',
            'status' => 'inactive',
        ]);

        $apartment->residents()->attach($resident->id, [
            'tenant_id' => $tenant->id,
            'tenancy_type' => 'owner',
            'occupancy_status' => 'active',
            'ownership_percentage' => 75.50,
            'move_in_at' => '2026-01-01 00:00:00',
            'move_out_at' => null,
            'is_primary_contact' => true,
        ]);

        $this->assertCount(1, $tenant->residents);
        $this->assertTrue($resident->tenant->is($tenant));
        $this->assertCount(1, Resident::forTenant($tenant)->active()->owners()->get());
        $this->assertSame('Fatma Ali', $resident->full_name);

        $residentApartment = $resident->load('apartments')->apartments->first();

        $this->assertTrue($residentApartment->is($apartment));
        $this->assertSame('owner', $residentApartment->pivot->tenancy_type);
        $this->assertSame('active', $residentApartment->pivot->occupancy_status);
        $this->assertSame(75.5, (float) $residentApartment->pivot->ownership_percentage);
        $this->assertTrue((bool) $residentApartment->pivot->is_primary_contact);

        $apartmentResident = $apartment->load('residents')->residents->first();

        $this->assertTrue($apartmentResident->is($resident));
        $this->assertSame($tenant->id, $apartmentResident->pivot->tenant_id);
    }

    public function test_duplicate_apartment_resident_links_are_not_allowed(): void
    {
        $tenant = Tenant::factory()->create();
        $building = Building::factory()->create([
            'tenant_id' => $tenant->id,
        ]);
        $apartment = Apartment::factory()->forBuilding($building)->create([
            'tenant_id' => $tenant->id,
        ]);
        $resident = Resident::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $apartment->residents()->attach($resident->id, [
            'tenant_id' => $tenant->id,
            'tenancy_type' => 'tenant',
            'occupancy_status' => 'active',
            'ownership_percentage' => 0,
            'move_in_at' => '2026-02-01 00:00:00',
            'is_primary_contact' => false,
        ]);

        $this->expectException(QueryException::class);

        $apartment->residents()->attach($resident->id, [
            'tenant_id' => $tenant->id,
            'tenancy_type' => 'tenant',
            'occupancy_status' => 'active',
            'ownership_percentage' => 0,
            'move_in_at' => '2026-03-01 00:00:00',
            'is_primary_contact' => false,
        ]);
    }
}
