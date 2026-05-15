<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreApartmentRequest;
use App\Http\Requests\Admin\UpdateApartmentRequest;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApartmentController extends Controller
{
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        return view('admin.apartments.index', [
            'apartments' => Apartment::query()
                ->forTenant($user->tenant_id)
                ->with('building')
                ->latest('id')
                ->paginate(15),
        ]);
    }

    public function create(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        return view('admin.apartments.create', [
            'apartment' => new Apartment(),
            'buildings' => Building::query()->forTenant($user->tenant_id)->orderBy('name')->get(),
        ]);
    }

    public function store(StoreApartmentRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $apartment = Apartment::query()->create([
            ...$request->validated(),
            'tenant_id' => $user->tenant_id,
        ]);

        return redirect()
            ->route('admin.apartments.show', $apartment)
            ->with('status', 'Apartment created successfully.');
    }

    public function show(Request $request, Apartment $apartment): View
    {
        $this->ensureTenantApartment($request, $apartment);

        return view('admin.apartments.show', [
            'apartment' => $apartment->load('building'),
        ]);
    }

    public function edit(Request $request, Apartment $apartment): View
    {
        $this->ensureTenantApartment($request, $apartment);

        /** @var User $user */
        $user = $request->user();

        return view('admin.apartments.edit', [
            'apartment' => $apartment,
            'buildings' => Building::query()->forTenant($user->tenant_id)->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateApartmentRequest $request, Apartment $apartment): RedirectResponse
    {
        $this->ensureTenantApartment($request, $apartment);
        $apartment->fill($request->validated())->save();

        return redirect()
            ->route('admin.apartments.show', $apartment)
            ->with('status', 'Apartment updated successfully.');
    }

    public function destroy(Request $request, Apartment $apartment): RedirectResponse
    {
        $this->ensureTenantApartment($request, $apartment);
        $apartment->delete();

        return redirect()
            ->route('admin.apartments.index')
            ->with('status', 'Apartment deleted successfully.');
    }

    private function ensureTenantApartment(Request $request, Apartment $apartment): void
    {
        if ($request->user()?->tenant_id !== $apartment->tenant_id) {
            abort(403, 'Cross-tenant access is not allowed.');
        }
    }
}
