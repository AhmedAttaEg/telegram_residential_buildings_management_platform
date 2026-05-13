<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Collection;

class NotificationRecipientResolver
{
    /**
     * @return Collection<int, User>
     */
    public function tenantOwners(Tenant $tenant): Collection
    {
        return User::query()
            ->forTenant($tenant)
            ->active()
            ->whereHas('roles', fn ($query) => $query->where('slug', 'tenant_owner'))
            ->get();
    }

    /**
     * @param  Collection<int, Tenant>  $tenants
     * @return Collection<int, Collection<int, User>>
     */
    public function tenantOwnersForTenants(Collection $tenants): Collection
    {
        $tenantIds = $tenants
            ->pluck('id')
            ->unique()
            ->values();

        if ($tenantIds->isEmpty()) {
            return collect();
        }

        return User::query()
            ->whereIn('tenant_id', $tenantIds)
            ->active()
            ->whereHas('roles', fn ($query) => $query->where('slug', 'tenant_owner'))
            ->get()
            ->groupBy('tenant_id');
    }
}
