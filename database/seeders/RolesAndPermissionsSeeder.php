<?php

namespace Database\Seeders;

use App\Modules\Core\Support\PermissionRegistry;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Create every permission declared by the modules and apply the role matrix.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        PermissionRegistry::syncPermissions();

        foreach (PermissionRegistry::ROLES as $role) {
            Role::findOrCreate($role, 'web');
        }

        foreach (PermissionRegistry::roleMatrix() as $roleName => $permissions) {
            $role = Role::findByName($roleName, 'web');

            $role->syncPermissions(
                $permissions === ['*'] ? PermissionRegistry::all() : $permissions
            );
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
