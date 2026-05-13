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
use App\Services\SubscriptionReminderService;
use App\Services\WalletService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PerformanceOptimizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_resident_wallet_history_query_count_remains_bounded_as_transaction_volume_grows(): void
    {
        [$tenant, $residentUser, $resident, $apartment, $period] = $this->createResidentFixture();
        $walletService = app(WalletService::class);

        Sanctum::actingAs($residentUser->load('roles.permissions', 'resident'));

        $walletService->deposit($apartment, 50, [
            'resident' => $resident,
            'financial_period' => $period,
            'description' => 'Seed transaction',
        ]);

        $baseline = $this->queryCountFor(function () use ($tenant, $apartment): void {
            $this->getJson("/api/v1/t/{$tenant->slug}/resident/apartments/{$apartment->id}/wallet/history?per_page=15")
                ->assertOk();
        });

        foreach (range(1, 24) as $index) {
            $walletService->deposit($apartment, 10 + $index, [
                'resident' => $resident,
                'financial_period' => $period,
                'description' => 'Transaction '.$index,
            ]);
        }

        $expanded = $this->queryCountFor(function () use ($tenant, $apartment): void {
            $this->getJson("/api/v1/t/{$tenant->slug}/resident/apartments/{$apartment->id}/wallet/history?per_page=15")
                ->assertOk();
        });

        $this->assertLessThanOrEqual($baseline + 1, $expanded);
    }

    public function test_auth_profile_query_count_remains_bounded_with_multiple_roles_and_permissions(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->forTenant($tenant)->create();
        $user->roles()->attach($this->roleId('resident'));
        $token = $user->createToken('ios-app', $user->fresh()->permissionSlugs()->all());

        $user->roles()->syncWithoutDetaching([
            $this->roleId('accountant'),
            $this->roleId('maintenance'),
            $this->roleId('tenant_owner'),
        ]);

        $expanded = $this->queryCountFor(function () use ($token): void {
            $this->withToken($token->plainTextToken)
                ->getJson('/api/v1/auth/me')
                ->assertOk();
        });

        $this->assertLessThanOrEqual(6, $expanded);
    }

    public function test_subscription_grace_notifications_query_count_remains_batched_across_multiple_tenants(): void
    {
        $this->fakeQueue();

        $service = app(SubscriptionReminderService::class);

        $this->createGraceTenantFixture();

        $baseline = $this->queryCountFor(function () use ($service): void {
            $service->sendGraceNotifications();
        });

        Tenant::query()->delete();
        User::query()->delete();

        foreach (range(1, 5) as $index) {
            $this->createGraceTenantFixture("Grace Tenant {$index}");
        }

        $expanded = $this->queryCountFor(function () use ($service): void {
            $service->sendGraceNotifications();
        });

        $this->assertLessThanOrEqual($baseline + 2, $expanded);
    }

    public function test_explain_plans_use_expected_indexes_for_heavy_queries(): void
    {
        $tenant = Tenant::factory()->create();
        $building = Building::factory()->create([
            'tenant_id' => $tenant->id,
        ]);
        $apartment = Apartment::factory()->forBuilding($building)->create([
            'tenant_id' => $tenant->id,
        ]);
        $period = FinancialPeriod::query()->create([
            'tenant_id' => $tenant->id,
            'name' => '2026-01',
            'period_type' => 'monthly',
            'starts_at' => '2026-01-01',
            'ends_at' => '2026-01-31',
            'status' => 'open',
        ]);
        $owner = User::factory()->forTenant($tenant)->create();
        $owner->roles()->attach($this->roleId('tenant_owner'));

        $expense = Expense::query()->create([
            'tenant_id' => $tenant->id,
            'building_id' => $building->id,
            'financial_period_id' => $period->id,
            'created_by' => $owner->id,
            'title' => 'Shared expense',
            'expense_date' => $period->starts_at,
            'status' => 'approved',
            'total_amount' => 125,
            'currency' => 'EGP',
        ]);

        ExpenseSplit::query()->create([
            'tenant_id' => $tenant->id,
            'expense_id' => $expense->id,
            'building_id' => $building->id,
            'apartment_id' => $apartment->id,
            'financial_period_id' => $period->id,
            'amount' => 125,
            'currency' => 'EGP',
            'is_confirmed' => true,
            'confirmed_at' => now(),
            'confirmed_by' => $owner->id,
            'is_paid' => false,
            'is_reversed' => false,
        ]);

        $walletService = app(WalletService::class);
        $walletService->deposit($apartment, 250, [
            'financial_period' => $period,
            'description' => 'Funding',
        ]);

        $walletPlan = $this->explain(
            'select * from wallet_transactions where tenant_id = ? and apartment_id = ? order by id desc limit 15',
            [$tenant->id, $apartment->id],
        );
        $splitPlan = $this->explain(
            'select * from expense_splits where tenant_id = ? and apartment_id = ? and is_confirmed = ? and is_paid = ? and is_reversed = ? order by id desc limit 15',
            [$tenant->id, $apartment->id, 1, 0, 0],
        );
        $notificationPlan = $this->explain(
            'select * from notifications where notifiable_type = ? and type = ? and notifiable_id in (?)',
            [User::class, 'App\\Notifications\\SubscriptionGraceNotification', $owner->id],
        );

        $this->assertPlanUsesAnyIndex($walletPlan, [
            'wallet_transactions_tenant_apartment_id_index',
            'wallet_transactions_tenant_id_apartment_id_index',
        ]);
        $this->assertPlanUsesAnyIndex($splitPlan, [
            'expense_splits_apartment_state_index',
            'expense_splits_tenant_id_building_id_index',
            'expense_splits_tenant_id_financial_period_id_index',
        ]);
        $this->assertPlanUsesAnyIndex($notificationPlan, [
            'notifications_notifiable_type_type_id_index',
            'notifications_notifiable_type_notifiable_id_index',
        ]);
    }

    /**
     * @return array{Tenant, User, Resident, Apartment, FinancialPeriod}
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

        return [$tenant, $residentUser, $resident, $apartment, $period];
    }

    private function createGraceTenantFixture(?string $name = null): void
    {
        $tenant = Tenant::factory()->create([
            'name' => $name ?? 'Grace Tenant',
            'subscription_status' => 'grace',
            'grace_ends_at' => now()->addDays(2),
        ]);
        $owner = User::factory()->forTenant($tenant)->create();
        $owner->roles()->attach($this->roleId('tenant_owner'));
    }

    /**
     * @param  array<int, mixed>  $bindings
     * @return array<int, object>
     */
    private function explain(string $sql, array $bindings): array
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            return DB::select('EXPLAIN QUERY PLAN '.$sql, $bindings);
        }

        return DB::select('EXPLAIN '.$sql, $bindings);
    }

    /**
     * @param  array<int, object>  $plan
     */
    private function assertPlanUsesAnyIndex(array $plan, array $expectedIndexes): void
    {
        $details = collect($plan)
            ->map(function (object $row): string {
                return strtolower(implode(' ', array_map(
                    static fn (mixed $value): string => strtolower((string) $value),
                    get_object_vars($row),
                )));
            })
            ->implode(' ');

        $matched = collect($expectedIndexes)
            ->contains(fn (string $expectedIndex): bool => str_contains($details, strtolower($expectedIndex)));

        $this->assertTrue($matched, sprintf(
            'Expected explain plan to use one of [%s]. Actual plan: %s',
            implode(', ', $expectedIndexes),
            $details,
        ));
    }

    private function roleId(string $slug): int
    {
        return (int) Role::query()->where('slug', $slug)->value('id');
    }
}
