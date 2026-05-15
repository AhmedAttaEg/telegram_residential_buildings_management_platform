<?php

namespace App\Support;

use App\Models\User;

class WebDashboardResolver
{
    public function pathFor(User $user): string
    {
        if ($user->isPlatformOwner() && $user->tenant_id === null) {
            return route('owner.dashboard');
        }

        if ($this->canAccessTenantAdmin($user)) {
            return route('admin.dashboard');
        }

        if ($this->canAccessResidentPortal($user)) {
            return route('resident.dashboard');
        }

        return route('home');
    }

    public function canAccessTenantAdmin(User $user): bool
    {
        if ($user->tenant_id === null) {
            return false;
        }

        if ($this->canAccessResidentPortal($user) && $user->roleSlugs()->count() === 1) {
            return false;
        }

        return $user->hasPermission('user.manage')
            || $user->hasPermission('building.manage')
            || $user->hasPermission('accounting.access')
            || $user->hasPermission('maintenance.access');
    }

    public function canAccessResidentPortal(User $user): bool
    {
        return $user->tenant_id !== null
            && $user->resident_id !== null
            && $user->hasPermission('resident.access');
    }
}
