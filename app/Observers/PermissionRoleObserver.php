<?php

namespace App\Observers;

use App\Models\Permission;
use App\Models\Pivots\PermissionRole;
use App\Models\Role;
use App\Services\AuditService;

class PermissionRoleObserver
{
    public function __construct(
        private readonly AuditService $auditService,
    ) {
    }

    public function created(PermissionRole $pivot): void
    {
        $role = Role::query()->find($pivot->role_id);

        if (! $role instanceof Role) {
            return;
        }

        $permissionIds = $this->permissionIds($role);
        $oldIds = array_values(array_diff($permissionIds, [$pivot->permission_id]));

        $this->auditService->recordCustomEvent(
            $role,
            'role_permission_attached',
            ['permission_ids' => $oldIds],
            ['permission_ids' => $permissionIds],
            [
                'source' => $this->auditService->source(),
                'permission_id' => $pivot->permission_id,
                'old_permission_slugs' => $this->permissionSlugs($oldIds),
                'new_permission_slugs' => $this->permissionSlugs($permissionIds),
            ],
        );
    }

    public function deleted(PermissionRole $pivot): void
    {
        $role = Role::query()->find($pivot->role_id);

        if (! $role instanceof Role) {
            return;
        }

        $newIds = $this->permissionIds($role);
        $oldIds = $newIds;
        $oldIds[] = (int) $pivot->permission_id;
        sort($oldIds);

        $this->auditService->recordCustomEvent(
            $role,
            'role_permission_detached',
            ['permission_ids' => $oldIds],
            ['permission_ids' => $newIds],
            [
                'source' => $this->auditService->source(),
                'permission_id' => $pivot->permission_id,
                'old_permission_slugs' => $this->permissionSlugs($oldIds),
                'new_permission_slugs' => $this->permissionSlugs($newIds),
            ],
        );
    }

    /**
     * @return list<int>
     */
    private function permissionIds(Role $role): array
    {
        return $role->permissions()
            ->orderBy('permissions.id')
            ->pluck('permissions.id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();
    }

    /**
     * @param  list<int>  $ids
     * @return array<int, string>
     */
    private function permissionSlugs(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return Permission::query()
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->pluck('slug', 'id')
            ->all();
    }
}
