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
            ->where('tenant_id', $tenant->id)
            ->where('status', 'active')
            ->whereHas('roles', fn ($query) => $query->where('slug', 'tenant_owner'))
            ->get();
    }
}
