<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBuildingRequest;
use App\Http\Requests\Admin\UpdateBuildingRequest;
use App\Models\Building;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BuildingController extends Controller
{
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        return view('admin.buildings.index', [
            'buildings' => Building::query()
                ->forTenant($user->tenant_id)
                ->latest('id')
                ->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('admin.buildings.create', [
            'building' => new Building(),
        ]);
    }

    public function store(StoreBuildingRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $building = Building::query()->create([
            ...$request->validated(),
            'tenant_id' => $user->tenant_id,
        ]);

        return redirect()
            ->route('admin.buildings.show', $building)
            ->with('status', 'Building created successfully.');
    }

    public function show(Request $request, Building $building): View
    {
        $this->ensureTenantBuilding($request, $building);

        return view('admin.buildings.show', [
            'building' => $building->loadCount('apartments'),
        ]);
    }

    public function edit(Request $request, Building $building): View
    {
        $this->ensureTenantBuilding($request, $building);

        return view('admin.buildings.edit', [
            'building' => $building,
        ]);
    }

    public function update(UpdateBuildingRequest $request, Building $building): RedirectResponse
    {
        $this->ensureTenantBuilding($request, $building);
        $building->fill($request->validated())->save();

        return redirect()
            ->route('admin.buildings.show', $building)
            ->with('status', 'Building updated successfully.');
    }

    public function destroy(Request $request, Building $building): RedirectResponse
    {
        $this->ensureTenantBuilding($request, $building);
        $building->delete();

        return redirect()
            ->route('admin.buildings.index')
            ->with('status', 'Building deleted successfully.');
    }

    private function ensureTenantBuilding(Request $request, Building $building): void
    {
        if ($request->user()?->tenant_id !== $building->tenant_id) {
            abort(403, 'Cross-tenant access is not allowed.');
        }
    }
}
