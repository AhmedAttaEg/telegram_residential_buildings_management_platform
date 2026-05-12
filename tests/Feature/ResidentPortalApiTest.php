<?php

namespace Tests\Feature;

use App\Models\Apartment;
use App\Models\Building;
use App\Models\Expense;
use App\Models\ExpenseSplit;
use App\Models\FinancialPeriod;
use App\Models\Resident;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Services\DebitService;
use App\Services\WalletService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ResidentPortalApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_resident_can_view_wallet_summary_and_history_for_an_accessible_apartment(): void
    {
        [$tenant, $residentUser, $resident, $apartment, $period] = $this->createResidentFixture();
        $walletService = app(WalletService::class);

        $walletService->deposit($apartment, 500, [
            'resident' => $resident,
            'financial_period' => $period,
            'description' => 'Initial deposit',
        ]);
        $walletService->deduct($apartment, 125, [
            'resident' => $resident,
            'financial_period' => $period,
            'type' => 'deduction',
            'description' => 'Service charge',
        ]);
        $walletService->deposit($apartment, 50, [
            'resident' => $resident,
            'financial_period' => $period,
            'description' => 'Adjustment',
        ]);

        Sanctum::actingAs($residentUser->load('roles.permissions', 'resident'));

        $this->getJson("/api/v1/t/{$tenant->slug}/resident/apartments/{$apartment->id}/wallet/summary")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.balance', 425)
            ->assertJsonPath('data.resident.id', $resident->id)
            ->assertJsonPath('data.apartment.id', $apartment->id);

        $this->getJson("/api/v1/t/{$tenant->slug}/resident/apartments/{$apartment->id}/wallet/history?per_page=2")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.pagination.per_page', 2)
            ->assertJsonPath('meta.pagination.total', 3)
            ->assertJsonPath('meta.apartment.id', $apartment->id)
            ->assertJsonPath('data.0.description', 'Adjustment')
            ->assertJsonPath('data.1.description', 'Service charge');
    }

    public function test_resident_can_view_debit_summary_and_filtered_unpaid_splits(): void
    {
        [$tenant, $residentUser, $resident, $apartment, $period, $building] = $this->createResidentFixture();
        $otherPeriod = FinancialPeriod::query()->create([
            'tenant_id' => $tenant->id,
            'name' => '2026-02',
            'period_type' => 'monthly',
            'starts_at' => '2026-02-01',
            'ends_at' => '2026-02-28',
            'status' => 'open',
        ]);

        $debitService = app(DebitService::class);
        $debitService->createManualDebit($apartment, 50, [
            'resident' => $resident,
            'financial_period' => $period,
            'description' => 'Manual charge',
        ]);
        $debitService->recordPayment($apartment, 25, [
            'resident' => $resident,
            'financial_period' => $period,
            'description' => 'Debit payment',
        ]);

        $keptSplit = $this->createSplit($tenant, $building, $apartment, $period, 200, 'Water bill');
        $filteredOutByPeriod = $this->createSplit($tenant, $building, $apartment, $otherPeriod, 75, 'Parking fee');

        Sanctum::actingAs($residentUser->load('roles.permissions', 'resident'));

        $this->getJson("/api/v1/t/{$tenant->slug}/resident/apartments/{$apartment->id}/debit/summary")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.balance', 300)
            ->assertJsonPath('data.apartment.id', $apartment->id);

        $this->getJson("/api/v1/t/{$tenant->slug}/resident/apartments/{$apartment->id}/debit/unpaid-splits?financial_period_id={$period->id}&building_id={$building->id}&per_page=5")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $keptSplit->id)
            ->assertJsonPath('data.0.expense.title', 'Water bill')
            ->assertJsonPath('meta.filters.building_id', $building->id)
            ->assertJsonPath('meta.filters.financial_period_id', $period->id);

        $this->assertNotSame($keptSplit->id, $filteredOutByPeriod->id);
    }

    public function test_resident_portal_enforces_permission_feature_binding_and_apartment_access_rules(): void
    {
        [$tenant, $residentUser, $resident, $apartment] = $this->createResidentFixture();

        $maintenanceUser = User::factory()->forResident($resident)->create();
        $maintenanceUser->roles()->attach($this->roleId('maintenance'));

        Sanctum::actingAs($maintenanceUser->load('roles.permissions', 'resident'));

        $this->getJson("/api/v1/t/{$tenant->slug}/resident/apartments/{$apartment->id}/wallet/summary")
            ->assertForbidden()
            ->assertJsonPath('message', 'Permission [resident.access] is required.');

        $noResidentLinkUser = User::factory()->forTenant($tenant)->create();
        $noResidentLinkUser->roles()->attach($this->roleId('resident'));

        Sanctum::actingAs($noResidentLinkUser->load('roles.permissions'));

        $this->getJson("/api/v1/t/{$tenant->slug}/resident/apartments/{$apartment->id}/wallet/summary")
            ->assertForbidden()
            ->assertJsonPath('message', 'Resident profile is required.');

        $tenant->update([
            'feature_flags' => array_replace(config('tenant.features', []), [
                'resident_app' => false,
            ]),
        ]);

        Sanctum::actingAs($residentUser->load('roles.permissions', 'resident'));

        $this->getJson("/api/v1/t/{$tenant->slug}/resident/apartments/{$apartment->id}/wallet/summary")
            ->assertForbidden()
            ->assertJsonPath('message', 'Tenant feature [resident_app] is disabled.');
    }

    public function test_resident_portal_enforces_tenant_isolation_and_active_occupancy(): void
    {
        [$tenant, $residentUser, $resident, $apartment] = $this->createResidentFixture();
        [$otherTenant, , , $otherApartment] = $this->createResidentFixture();

        $sameTenantOtherApartment = Apartment::factory()->forBuilding(Building::factory()->create([
            'tenant_id' => $tenant->id,
        ]))->create([
            'tenant_id' => $tenant->id,
        ]);

        $otherResident = Resident::factory()->create([
            'tenant_id' => $tenant->id,
        ]);
        $sameTenantOtherApartment->residents()->attach($otherResident->id, [
            'tenant_id' => $tenant->id,
            'tenancy_type' => 'tenant',
            'occupancy_status' => 'active',
            'move_in_at' => now(),
            'move_out_at' => null,
            'is_primary_contact' => true,
        ]);

        Sanctum::actingAs($residentUser->load('roles.permissions', 'resident'));

        $this->getJson("/api/v1/t/{$otherTenant->slug}/resident/apartments/{$otherApartment->id}/wallet/summary")
            ->assertForbidden()
            ->assertJsonPath('message', 'Cross-tenant access is not allowed.');

        $this->getJson("/api/v1/t/{$tenant->slug}/resident/apartments/{$sameTenantOtherApartment->id}/wallet/summary")
            ->assertForbidden()
            ->assertJsonPath('message', 'Apartment access is not allowed.');

        $this->getJson("/api/v1/t/{$tenant->slug}/resident/apartments/{$otherApartment->id}/wallet/summary")
            ->assertForbidden()
            ->assertJsonPath('message', 'Apartment access is not allowed.');

        $apartment->residents()->updateExistingPivot($resident->id, [
            'occupancy_status' => 'inactive',
            'move_out_at' => now(),
        ]);

        $this->getJson("/api/v1/t/{$tenant->slug}/resident/apartments/{$apartment->id}/wallet/summary")
            ->assertForbidden()
            ->assertJsonPath('message', 'Apartment access is not allowed.');
    }

    /**
     * @return array{Tenant, User, Resident, Apartment, FinancialPeriod, Building}
     */
    private function createResidentFixture(): array
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
        $residentUser = User::factory()->forResident($resident)->create();
        $residentUser->roles()->attach($this->roleId('resident'));
        $apartment->residents()->attach($resident->id, [
            'tenant_id' => $tenant->id,
            'tenancy_type' => 'tenant',
            'occupancy_status' => 'active',
            'move_in_at' => now()->subMonth(),
            'move_out_at' => null,
            'is_primary_contact' => true,
        ]);
        $period = FinancialPeriod::query()->create([
            'tenant_id' => $tenant->id,
            'name' => '2026-01',
            'period_type' => 'monthly',
            'starts_at' => '2026-01-01',
            'ends_at' => '2026-01-31',
            'status' => 'open',
        ]);

        return [$tenant, $residentUser, $resident, $apartment, $period, $building];
    }

    private function createSplit(
        Tenant $tenant,
        Building $building,
        Apartment $apartment,
        FinancialPeriod $period,
        float $amount,
        string $title,
    ): ExpenseSplit {
        $expense = Expense::query()->create([
            'tenant_id' => $tenant->id,
            'building_id' => $building->id,
            'financial_period_id' => $period->id,
            'created_by' => User::factory()->forTenant($tenant)->create()->id,
            'title' => $title,
            'expense_date' => $period->starts_at,
            'status' => 'approved',
            'total_amount' => $amount,
            'currency' => 'EGP',
        ]);

        return ExpenseSplit::query()->create([
            'tenant_id' => $tenant->id,
            'expense_id' => $expense->id,
            'building_id' => $building->id,
            'apartment_id' => $apartment->id,
            'financial_period_id' => $period->id,
            'amount' => $amount,
            'currency' => 'EGP',
            'is_confirmed' => true,
            'confirmed_at' => now(),
            'confirmed_by' => $expense->created_by,
            'is_paid' => false,
            'is_reversed' => false,
        ]);
    }

    private function roleId(string $slug): int
    {
        return (int) Role::query()->where('slug', $slug)->value('id');
    }
}
