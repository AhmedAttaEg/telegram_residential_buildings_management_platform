<?php

namespace App\Http\Middleware;

use App\Models\Apartment;
use App\Models\Resident;
use App\Models\Tenant;
use App\Models\User;
use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureResidentHasApartmentAccess
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = $request->user();
        /** @var Tenant|null $tenant */
        $tenant = $request->attributes->get('tenant');
        /** @var Resident|null $resident */
        $resident = $user?->resident;
        $apartment = $request->route('apartment');

        if (! $resident instanceof Resident) {
            return ApiResponse::error('Resident profile is required.', Response::HTTP_FORBIDDEN);
        }

        if (! $tenant instanceof Tenant || $resident->tenant_id !== $tenant->id || $user?->tenant_id !== $tenant->id) {
            return ApiResponse::error('Resident tenant mismatch.', Response::HTTP_FORBIDDEN);
        }

        if (is_string($apartment)) {
            $apartment = Apartment::query()->find($apartment);
        }

        if (! $apartment instanceof Apartment || $apartment->tenant_id !== $tenant->id) {
            return ApiResponse::error('Apartment access is not allowed.', Response::HTTP_FORBIDDEN);
        }

        $isAccessible = $resident->apartments()
            ->where('apartments.id', $apartment->id)
            ->wherePivot('occupancy_status', 'active')
            ->wherePivotNull('move_out_at')
            ->exists();

        if (! $isAccessible) {
            return ApiResponse::error('Apartment access is not allowed.', Response::HTTP_FORBIDDEN);
        }

        $request->attributes->set('resident', $resident);
        $request->attributes->set('resident_apartment', $apartment->loadMissing('building'));

        return $next($request);
    }
}
