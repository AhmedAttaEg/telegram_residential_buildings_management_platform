<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $permissions = [
            'tenant.manage' => 'Manage tenants',
            'subscription.manage' => 'Manage subscriptions',
            'feature.manage' => 'Manage tenant features',
            'user.manage' => 'Manage users',
            'building.manage' => 'Manage buildings',
            'accounting.access' => 'Access accounting',
            'maintenance.access' => 'Access maintenance',
            'resident.access' => 'Access resident area',
            'audit.access' => 'Access audit logs',
        ];

        foreach ($permissions as $slug => $name) {
            Permission::query()->updateOrCreate(
                ['slug' => $slug],
                ['name' => $name],
            );
        }

        $roles = [
            'platform_owner' => [
                'name' => 'Platform Owner',
                'permissions' => array_keys($permissions),
            ],
            'tenant_owner' => [
                'name' => 'Tenant Owner',
                'permissions' => [
                    'user.manage',
                    'building.manage',
                    'accounting.access',
                    'maintenance.access',
                    'resident.access',
                ],
            ],
            'accountant' => [
                'name' => 'Accountant',
                'permissions' => [
                    'accounting.access',
                    'resident.access',
                ],
            ],
            'maintenance' => [
                'name' => 'Maintenance',
                'permissions' => [
                    'maintenance.access',
                ],
            ],
            'resident' => [
                'name' => 'Resident',
                'permissions' => [
                    'resident.access',
                ],
            ],
        ];

        foreach ($roles as $slug => $roleData) {
            $role = Role::query()->updateOrCreate(
                ['slug' => $slug],
                ['name' => $roleData['name']],
            );

            $role->permissions()->sync(
                Permission::query()->whereIn('slug', $roleData['permissions'])->pluck('id'),
            );
        }
    }
}
