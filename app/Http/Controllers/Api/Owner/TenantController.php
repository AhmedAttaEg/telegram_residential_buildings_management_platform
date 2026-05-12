<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\ListTenantsRequest;
use App\Http\Requests\Owner\StoreTenantRequest;
use App\Http\Requests\Owner\UpdateTenantRequest;
use App\Http\Requests\Owner\UpdateTenantStatusRequest;
use App\Models\Tenant;
use App\Services\TenantSuspensionService;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

class TenantController extends Controller
{
    public function __construct(
        private readonly TenantSuspensionService $tenantSuspensionService,
    ) {
    }

    public function index(ListTenantsRequest $request)
    {
        $query = Tenant::query()->latest('id');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('subscription_status')) {
            $query->where('subscription_status', $request->string('subscription_status'));
        }

        if ($request->filled('subscription_plan')) {
            $query->where('subscription_plan', $request->string('subscription_plan'));
        }

        if ($request->filled('search')) {
            $search = (string) $request->string('search');

            $query->where(function ($builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        $tenants = $query->paginate((int) $request->integer('per_page', 15));

        return $this->apiPaginated($tenants);
    }

    public function store(StoreTenantRequest $request)
    {
        $validated = $request->validated();
        $validated['feature_flags'] = array_replace(config('tenant.features', []), $validated['feature_flags'] ?? []);
        $validated['brand_name'] ??= $validated['name'];
        $validated['status'] ??= 'active';
        $validated['subscription_status'] ??= 'trial';

        $tenant = Tenant::query()->create($validated);

        return $this->apiSuccess($tenant, 'Tenant created successfully.', Response::HTTP_CREATED);
    }

    public function show(Tenant $tenant)
    {
        return $this->apiSuccess($tenant);
    }

    public function update(UpdateTenantRequest $request, Tenant $tenant)
    {
        $validated = $request->validated();

        if (array_key_exists('feature_flags', $validated)) {
            $validated['feature_flags'] = array_replace(config('tenant.features', []), $validated['feature_flags']);
        }

        $tenant->fill($validated)->save();

        return $this->apiSuccess($tenant->refresh(), 'Tenant updated successfully.');
    }

    public function updateStatus(UpdateTenantStatusRequest $request, Tenant $tenant)
    {
        $action = $request->string('action')->toString();
        $reason = $request->input('reason');

        $tenant = match ($action) {
            'activate' => $this->tenantSuspensionService->activate($tenant),
            'grace' => $this->tenantSuspensionService->placeInGrace(
                $tenant,
                $request->filled('grace_ends_at') ? Carbon::parse((string) $request->input('grace_ends_at')) : null,
                $reason,
            ),
            'suspend' => $this->tenantSuspensionService->suspend($tenant, $reason),
            'remind' => $this->tenantSuspensionService->markReminderSent($tenant),
        };

        return $this->apiSuccess($tenant, 'Tenant status updated successfully.');
    }

    public function destroy(Tenant $tenant)
    {
        $tenant->delete();

        return $this->apiNoContent();
    }
}
