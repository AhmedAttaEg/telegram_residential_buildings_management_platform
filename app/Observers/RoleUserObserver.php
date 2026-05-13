<?php

namespace App\Observers;

use App\Models\Pivots\RoleUser;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditService;

class RoleUserObserver
{
    public function __construct(
        private readonly AuditService $auditService,
    ) {
    }

    public function created(RoleUser $pivot): void
    {
        $user = User::query()->find($pivot->user_id);

        if (! $user instanceof User) {
            return;
        }

        $roleIds = $this->roleIds($user);
        $oldIds = array_values(array_diff($roleIds, [$pivot->role_id]));

        $this->auditService->recordCustomEvent(
            $user,
            'user_role_attached',
            ['role_ids' => $oldIds],
            ['role_ids' => $roleIds],
            [
                'source' => $this->auditService->source(),
                'role_id' => $pivot->role_id,
                'old_role_slugs' => $this->roleSlugs($oldIds),
                'new_role_slugs' => $this->roleSlugs($roleIds),
            ],
        );
    }

    public function deleted(RoleUser $pivot): void
    {
        $user = User::query()->find($pivot->user_id);

        if (! $user instanceof User) {
            return;
        }

        $newIds = $this->roleIds($user);
        $oldIds = $newIds;
        $oldIds[] = (int) $pivot->role_id;
        sort($oldIds);

        $this->auditService->recordCustomEvent(
            $user,
            'user_role_detached',
            ['role_ids' => $oldIds],
            ['role_ids' => $newIds],
            [
                'source' => $this->auditService->source(),
                'role_id' => $pivot->role_id,
                'old_role_slugs' => $this->roleSlugs($oldIds),
                'new_role_slugs' => $this->roleSlugs($newIds),
            ],
        );
    }

    /**
     * @return list<int>
     */
    private function roleIds(User $user): array
    {
        return $user->roles()
            ->orderBy('roles.id')
            ->pluck('roles.id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();
    }

    /**
     * @param  list<int>  $ids
     * @return array<int, string>
     */
    private function roleSlugs(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return Role::query()
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->pluck('slug', 'id')
            ->all();
    }
}
