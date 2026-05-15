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
            return $this->forbidden($request, 'Resident profile is required.');
        }

        if (! $tenant instanceof Tenant || $resident->tenant_id !== $tenant->id || $user?->tenant_id !== $tenant->id) {
            return $this->forbidden($request, 'Resident tenant mismatch.');
        }

        if (is_string($apartment)) {
            $apartment = Apartment::query()->find($apartment);
        }

        if (! $apartment instanceof Apartment || $apartment->tenant_id !== $tenant->id) {
            return $this->forbidden($request, 'Apartment access is not allowed.');
        }

        $isAccessible = $resident->apartments()
            ->where('apartments.id', $apartment->id)
            ->wherePivot('occupancy_status', 'active')
            ->wherePivotNull('move_out_at')
            ->exists();

        if (! $isAccessible) {
            return $this->forbidden($request, 'Apartment access is not allowed.');
        }

        $request->attributes->set('resident', $resident);
        $request->attributes->set('resident_apartment', $apartment->loadMissing('building'));

        return $next($request);
    }

    private function forbidden(Request $request, string $message): Response
    {
        if (ApiResponse::isApiRequest($request)) {
            return ApiResponse::error($message, Response::HTTP_FORBIDDEN);
        }

        abort(Response::HTTP_FORBIDDEN, $message);
    }
}
