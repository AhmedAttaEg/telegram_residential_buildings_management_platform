<?php

namespace App\Http\Controllers\Web\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\ListTenantsRequest;
use App\Http\Requests\Owner\StoreTenantRequest;
use App\Http\Requests\Owner\UpdateTenantRequest;
use App\Http\Requests\Owner\UpdateTenantStatusRequest;
use App\Models\Tenant;
use App\Services\TenantSuspensionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class TenantController extends Controller
{
    public function __construct(
        private readonly TenantSuspensionService $tenantSuspensionService,
    ) {
    }

    public function index(ListTenantsRequest $request): View
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

        return view('owner.tenants.index', [
            'tenants' => $query->paginate((int) $request->integer('per_page', 15))->withQueryString(),
            'filters' => $request->only(['status', 'subscription_status', 'subscription_plan', 'search', 'per_page']),
        ]);
    }

    public function create(): View
    {
        return view('owner.tenants.create', [
            'tenant' => new Tenant(),
            'featureDefaults' => config('tenant.features', []),
        ]);
    }

    public function store(StoreTenantRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['feature_flags'] = $this->featureFlags($request);
        $validated['brand_name'] ??= $validated['name'];
        $validated['status'] ??= 'active';
        $validated['subscription_status'] ??= 'trial';

        $tenant = Tenant::query()->create($validated);

        return redirect()
            ->route('owner.tenants.show', $tenant)
            ->with('status', 'Tenant created successfully.');
    }

    public function show(Tenant $tenant): View
    {
        return view('owner.tenants.show', [
            'tenant' => $tenant,
            'features' => $tenant->featureFlags(),
        ]);
    }

    public function edit(Tenant $tenant): View
    {
        return view('owner.tenants.edit', [
            'tenant' => $tenant,
            'featureDefaults' => $tenant->featureFlags(),
        ]);
    }

    public function update(UpdateTenantRequest $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validated();
        $validated['feature_flags'] = $this->featureFlags($request);

        $tenant->fill($validated)->save();

        return redirect()
            ->route('owner.tenants.show', $tenant)
            ->with('status', 'Tenant updated successfully.');
    }

    public function destroy(Tenant $tenant): RedirectResponse
    {
        $tenant->delete();

        return redirect()
            ->route('owner.tenants.index')
            ->with('status', 'Tenant deleted successfully.');
    }

    public function updateStatus(UpdateTenantStatusRequest $request, Tenant $tenant): RedirectResponse
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

        return redirect()
            ->route('owner.tenants.show', $tenant)
            ->with('status', 'Tenant status updated successfully.');
    }

    /**
     * @return array<string, bool>
     */
    private function featureFlags(\Illuminate\Http\Request $request): array
    {
        $flags = [];

        foreach (array_keys(config('tenant.features', [])) as $feature) {
            $flags[$feature] = $request->boolean("feature_flags.{$feature}");
        }

        return $flags;
    }
}
