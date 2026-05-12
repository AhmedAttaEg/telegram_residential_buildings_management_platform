<?php

namespace Tests\Feature;

use App\Models\Apartment;
use App\Models\Building;
use App\Models\Resident;
use App\Models\Tenant;
use App\Models\Ticket;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MaintenanceDomainTest extends TestCase
{
    use RefreshDatabase;

    public function test_maintenance_tables_exist_with_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('tickets'));
        $this->assertTrue(Schema::hasColumns('tickets', [
            'tenant_id',
            'building_id',
            'apartment_id',
            'resident_id',
            'created_by',
            'assigned_to',
            'priority',
            'status',
            'opened_at',
            'resolved_at',
            'closed_at',
        ]));

        $this->assertTrue(Schema::hasTable('work_orders'));
        $this->assertTrue(Schema::hasColumns('work_orders', [
            'tenant_id',
            'ticket_id',
            'assigned_to',
            'created_by',
            'work_order_number',
            'status',
            'scheduled_for',
            'completed_at',
            'sla_target_at',
            'sla_breached_at',
        ]));
    }

    public function test_common_area_and_apartment_tickets_support_relationships_and_defaults(): void
    {
        [$tenant, $creator, $assignee, $building, $apartment, $resident] = $this->createMaintenanceFixture();

        $commonTicket = Ticket::query()->create([
            'tenant_id' => $tenant->id,
            'building_id' => $building->id,
            'created_by' => $creator->id,
            'title' => 'Lobby light outage',
            'description' => 'Light fixture is not working.',
        ]);

        $unitTicket = Ticket::query()->create([
            'tenant_id' => $tenant->id,
            'building_id' => $building->id,
            'apartment_id' => $apartment->id,
            'resident_id' => $resident->id,
            'created_by' => $creator->id,
            'assigned_to' => $assignee->id,
            'title' => 'Leaking sink',
            'description' => 'Kitchen sink is leaking.',
            'priority' => Ticket::PRIORITY_HIGH,
            'status' => Ticket::STATUS_IN_PROGRESS,
        ]);

        $this->assertSame(Ticket::PRIORITY_MEDIUM, $commonTicket->priority);
        $this->assertSame(Ticket::STATUS_OPEN, $commonTicket->status);
        $this->assertNotNull($commonTicket->opened_at);
        $this->assertNull($commonTicket->apartment_id);
        $this->assertNull($commonTicket->resident_id);

        $this->assertTrue($unitTicket->tenant->is($tenant));
        $this->assertTrue($unitTicket->building->is($building));
        $this->assertTrue($unitTicket->apartment->is($apartment));
        $this->assertTrue($unitTicket->resident->is($resident));
        $this->assertTrue($unitTicket->createdBy->is($creator));
        $this->assertTrue($unitTicket->assignedTo->is($assignee));

        $this->assertCount(2, $tenant->tickets);
        $this->assertCount(2, $building->tickets);
        $this->assertCount(1, $apartment->tickets);
        $this->assertCount(1, $resident->tickets);
        $this->assertCount(2, $creator->createdTickets);
        $this->assertCount(1, $assignee->assignedTickets);
    }

    public function test_work_orders_link_to_tickets_and_generate_tenant_unique_numbers(): void
    {
        [$tenant, $creator, $assignee, $building, $apartment, $resident] = $this->createMaintenanceFixture();

        $ticket = Ticket::query()->create([
            'tenant_id' => $tenant->id,
            'building_id' => $building->id,
            'apartment_id' => $apartment->id,
            'resident_id' => $resident->id,
            'created_by' => $creator->id,
            'title' => 'Air conditioner issue',
            'description' => 'Cooling is intermittent.',
            'priority' => Ticket::PRIORITY_URGENT,
        ]);

        $firstWorkOrder = WorkOrder::query()->create([
            'tenant_id' => $tenant->id,
            'ticket_id' => $ticket->id,
            'assigned_to' => $assignee->id,
            'created_by' => $creator->id,
            'title' => 'Inspect AC unit',
            'description' => 'Check refrigerant and compressor.',
            'scheduled_for' => '2026-05-12 10:00:00',
            'due_at' => '2026-05-12 14:00:00',
            'sla_target_at' => '2026-05-12 12:00:00',
        ]);

        $secondWorkOrder = WorkOrder::query()->create([
            'tenant_id' => $tenant->id,
            'ticket_id' => $ticket->id,
            'created_by' => $creator->id,
            'title' => 'Follow-up AC repair',
            'description' => 'Replace damaged component if needed.',
        ]);

        $this->assertSame(WorkOrder::STATUS_OPEN, $firstWorkOrder->status);
        $this->assertMatchesRegularExpression('/^WO-\d{6}-000001$/', $firstWorkOrder->work_order_number);
        $this->assertMatchesRegularExpression('/^WO-\d{6}-000002$/', $secondWorkOrder->work_order_number);
        $this->assertTrue($firstWorkOrder->ticket->is($ticket));
        $this->assertTrue($firstWorkOrder->tenant->is($tenant));
        $this->assertTrue($firstWorkOrder->createdBy->is($creator));
        $this->assertTrue($firstWorkOrder->assignedTo->is($assignee));
        $this->assertCount(2, $ticket->workOrders);
        $this->assertCount(2, $tenant->workOrders);
        $this->assertCount(2, $creator->createdWorkOrders);
        $this->assertCount(1, $assignee->assignedWorkOrders);
    }

    public function test_work_order_number_is_tenant_unique(): void
    {
        [$tenant, $creator, $assignee, $building] = $this->createMaintenanceFixtureWithoutUnit();

        $ticket = Ticket::query()->create([
            'tenant_id' => $tenant->id,
            'building_id' => $building->id,
            'created_by' => $creator->id,
            'title' => 'Generator inspection',
        ]);

        $manual = WorkOrder::query()->create([
            'tenant_id' => $tenant->id,
            'ticket_id' => $ticket->id,
            'assigned_to' => $assignee->id,
            'created_by' => $creator->id,
            'work_order_number' => 'WO-MANUAL-000001',
            'title' => 'Initial generator check',
        ]);

        $this->assertSame('WO-MANUAL-000001', $manual->work_order_number);
        $this->assertDatabaseHas('work_orders', [
            'tenant_id' => $tenant->id,
            'work_order_number' => 'WO-MANUAL-000001',
        ]);
    }

    /**
     * @return array{Tenant, User, User, Building, Apartment, Resident}
     */
    private function createMaintenanceFixture(): array
    {
        $tenant = Tenant::factory()->withFeature('maintenance', true)->create();
        $creator = User::factory()->forTenant($tenant)->create();
        $assignee = User::factory()->forTenant($tenant)->create();
        $building = Building::factory()->create([
            'tenant_id' => $tenant->id,
        ]);
        $apartment = Apartment::factory()->forBuilding($building)->create([
            'tenant_id' => $tenant->id,
        ]);
        $resident = Resident::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        return [$tenant, $creator, $assignee, $building, $apartment, $resident];
    }

    /**
     * @return array{Tenant, User, User, Building}
     */
    private function createMaintenanceFixtureWithoutUnit(): array
    {
        $tenant = Tenant::factory()->withFeature('maintenance', true)->create();
        $creator = User::factory()->forTenant($tenant)->create();
        $assignee = User::factory()->forTenant($tenant)->create();
        $building = Building::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        return [$tenant, $creator, $assignee, $building];
    }
}
