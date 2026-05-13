<?php

namespace Tests\Feature;

use App\Models\Apartment;
use App\Models\AuditLog;
use App\Models\Building;
use App\Models\FinancialPeriod;
use App\Models\LedgerAccount;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use App\Services\JournalEntryService;
use App\Services\TenantSubscriptionService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuditComplianceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_audit_logs_table_exists_with_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('audit_logs'));
        $this->assertTrue(Schema::hasColumns('audit_logs', [
            'tenant_id',
            'event',
            'actor_type',
            'actor_id',
            'subject_type',
            'subject_id',
            'old_values',
            'new_values',
            'metadata',
        ]));
    }

    public function test_audited_models_persist_old_and_new_values_and_skip_noop_saves(): void
    {
        $actor = User::factory()->create();
        $tenant = Tenant::factory()->create();

        $this->actingAs($actor);
        AuditLog::query()->delete();

        $account = LedgerAccount::query()->create([
            'tenant_id' => $tenant->id,
            'code' => '1000',
            'name' => 'Cash',
            'type' => LedgerAccount::TYPE_ASSET,
            'description' => 'Primary cash account',
            'is_active' => true,
        ]);

        $created = AuditLog::query()->where('event', 'ledger_account_created')->sole();

        $this->assertSame($tenant->id, $created->tenant_id);
        $this->assertSame($actor->id, $created->actor_id);
        $this->assertNull($created->old_values);
        $this->assertSame('Cash', $created->new_values['name']);
        $this->assertSame((string) $account->id, (string) $created->subject_id);

        AuditLog::query()->delete();

        $account->update([
            'name' => 'Cash Reserve',
            'description' => 'Updated cash account',
        ]);

        $updated = AuditLog::query()->where('event', 'ledger_account_updated')->sole();

        $this->assertSame('Cash', $updated->old_values['name']);
        $this->assertSame('Cash Reserve', $updated->new_values['name']);
        $this->assertSame('Primary cash account', $updated->old_values['description']);
        $this->assertSame('Updated cash account', $updated->new_values['description']);

        AuditLog::query()->delete();

        $account->save();

        $this->assertSame(0, AuditLog::query()->count());

        $account->delete();

        $deleted = AuditLog::query()->where('event', 'ledger_account_deleted')->sole();

        $this->assertSame('Cash Reserve', $deleted->old_values['name']);
        $this->assertNull($deleted->new_values);
    }

    public function test_subscription_and_tenant_lifecycle_changes_are_audited(): void
    {
        Carbon::setTestNow('2026-05-14 10:00:00');

        $owner = User::factory()->create();
        $owner->roles()->attach(Role::query()->where('slug', 'platform_owner')->value('id'));

        $tenant = Tenant::factory()->create();
        $plan = SubscriptionPlan::query()->create([
            'name' => 'Growth Monthly',
            'slug' => 'growth-monthly',
            'status' => SubscriptionPlan::STATUS_ACTIVE,
            'billing_cycle' => SubscriptionPlan::BILLING_CYCLE_MONTHLY,
            'price_amount' => 299.00,
            'currency' => 'EGP',
            'trial_days' => 7,
        ]);

        Sanctum::actingAs($owner);
        AuditLog::query()->delete();

        $subscription = app(TenantSubscriptionService::class)->attachPlan(
            $tenant,
            $plan,
            TenantSubscription::STATUS_TRIAL,
            now(),
            'Initial plan',
        );

        $subscriptionCreated = AuditLog::query()->where('event', 'tenant_subscription_created')->sole();

        $this->assertSame($tenant->id, $subscriptionCreated->tenant_id);
        $this->assertSame($owner->id, $subscriptionCreated->actor_id);
        $this->assertSame(TenantSubscription::STATUS_TRIAL, $subscriptionCreated->new_values['status']);

        $tenantUpdated = AuditLog::query()
            ->where('event', 'tenant_updated')
            ->latest('id')
            ->first();

        $this->assertNotNull($tenantUpdated);
        $this->assertSame('growth-monthly', $tenantUpdated->new_values['subscription_plan']);
        $this->assertArrayHasKey('trial_ends_at', $tenantUpdated->new_values);

        AuditLog::query()->delete();

        $this->patchJson("/api/v1/owner/tenants/{$tenant->slug}/status", [
            'action' => 'grace',
            'reason' => 'Invoice overdue',
            'grace_ends_at' => '2026-05-20 10:00:00',
        ])->assertOk();

        $statusAudit = AuditLog::query()
            ->where('event', 'tenant_updated')
            ->latest('id')
            ->first();

        $this->assertNotNull($statusAudit);
        $this->assertSame($owner->id, $statusAudit->actor_id);
        $this->assertSame('grace', $statusAudit->new_values['subscription_status']);
        $this->assertSame('Invoice overdue', $statusAudit->new_values['suspension_reason']);

        Carbon::setTestNow();
    }

    public function test_role_and_permission_assignments_are_audited_automatically(): void
    {
        $actor = User::factory()->create();
        $user = User::factory()->create();
        $role = Role::query()->create([
            'name' => 'Audit Role',
            'slug' => 'audit-role',
        ]);
        $permissionA = Permission::query()->create([
            'name' => 'Permission A',
            'slug' => 'permission-a',
        ]);
        $permissionB = Permission::query()->create([
            'name' => 'Permission B',
            'slug' => 'permission-b',
        ]);

        $this->actingAs($actor);
        AuditLog::query()->delete();

        $user->roles()->attach($role->id);

        $roleAttach = AuditLog::query()->where('event', 'user_role_attached')->sole();

        $this->assertSame($actor->id, $roleAttach->actor_id);
        $this->assertSame($user->id, $roleAttach->subject_id);
        $this->assertSame([], $roleAttach->old_values['role_ids']);
        $this->assertSame([$role->id], $roleAttach->new_values['role_ids']);

        AuditLog::query()->delete();

        $role->permissions()->attach($permissionA->id);

        $permissionAttach = AuditLog::query()->where('event', 'role_permission_attached')->sole();

        $this->assertSame($role->id, $permissionAttach->subject_id);
        $this->assertSame([], $permissionAttach->old_values['permission_ids']);
        $this->assertSame([$permissionA->id], $permissionAttach->new_values['permission_ids']);
        $this->assertSame('permission-a', $permissionAttach->metadata['new_permission_slugs'][$permissionA->id]);

        AuditLog::query()->delete();

        $role->permissions()->sync([$permissionB->id]);

        $events = AuditLog::query()
            ->whereIn('event', ['role_permission_detached', 'role_permission_attached'])
            ->orderBy('id')
            ->get()
            ->keyBy('event');

        $this->assertTrue($events->has('role_permission_detached'));
        $this->assertTrue($events->has('role_permission_attached'));
        $this->assertSame([$permissionA->id], $events['role_permission_detached']->old_values['permission_ids']);
        $this->assertSame([], $events['role_permission_detached']->new_values['permission_ids']);
        $this->assertSame([], $events['role_permission_attached']->old_values['permission_ids']);
        $this->assertSame([$permissionB->id], $events['role_permission_attached']->new_values['permission_ids']);
    }

    public function test_audit_rows_rollback_with_failed_transactions(): void
    {
        $actor = User::factory()->create();
        $tenant = Tenant::factory()->create();

        $this->actingAs($actor);
        AuditLog::query()->delete();

        try {
            DB::transaction(function () use ($tenant): void {
                LedgerAccount::query()->create([
                    'tenant_id' => $tenant->id,
                    'code' => '3000',
                    'name' => 'Temporary Account',
                    'type' => LedgerAccount::TYPE_ASSET,
                    'description' => 'Should rollback',
                    'is_active' => true,
                ]);

                throw new \RuntimeException('Force rollback');
            });
        } catch (\RuntimeException) {
        }

        $this->assertSame(0, AuditLog::query()->count());
    }

    public function test_accounting_journal_entry_operations_are_audited(): void
    {
        $actor = User::factory()->create();
        $tenant = Tenant::factory()->create();
        $building = Building::factory()->create([
            'tenant_id' => $tenant->id,
        ]);
        $apartment = Apartment::factory()->forBuilding($building)->create([
            'tenant_id' => $tenant->id,
        ]);
        $period = FinancialPeriod::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'May 2026',
            'starts_at' => '2026-05-01',
            'ends_at' => '2026-05-31',
            'status' => 'open',
        ]);
        $cash = LedgerAccount::query()->create([
            'tenant_id' => $tenant->id,
            'code' => '1100',
            'name' => 'Cash',
            'type' => LedgerAccount::TYPE_ASSET,
            'is_active' => true,
        ]);
        $revenue = LedgerAccount::query()->create([
            'tenant_id' => $tenant->id,
            'code' => '4100',
            'name' => 'Revenue',
            'type' => LedgerAccount::TYPE_REVENUE,
            'is_active' => true,
        ]);

        $this->actingAs($actor);
        AuditLog::query()->delete();

        $entry = app(JournalEntryService::class)->createDraftEntry(
            $tenant,
            $actor,
            $period,
            [
                [
                    'ledger_account_id' => $cash->id,
                    'description' => 'Cash receipt',
                    'debit_amount' => 500,
                    'credit_amount' => 0,
                ],
                [
                    'ledger_account_id' => $revenue->id,
                    'description' => 'Revenue recognition',
                    'debit_amount' => 0,
                    'credit_amount' => 500,
                ],
            ],
            [
                'description' => 'Owner payment',
            ],
        );

        $draftAudit = AuditLog::query()->where('event', 'journal_entry_created')->sole();
        $lineAudits = AuditLog::query()->where('event', 'journal_entry_line_created')->get();

        $this->assertSame($actor->id, $draftAudit->actor_id);
        $this->assertSame($tenant->id, $draftAudit->tenant_id);
        $this->assertCount(2, $lineAudits);

        AuditLog::query()->delete();

        app(JournalEntryService::class)->postEntry($entry, $actor);

        $posted = AuditLog::query()->where('event', 'journal_entry_updated')->sole();

        $this->assertSame('draft', $posted->old_values['status']);
        $this->assertSame('posted', $posted->new_values['status']);
    }
}
