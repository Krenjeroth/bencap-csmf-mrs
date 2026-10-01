<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Support\PermissionCatalog;
use Illuminate\Database\Seeder;

/**
 * Seeds the two specified roles. Safe to re-run.
 *
 * - System Administrator always holds every permission (re-synced on each run).
 * - Admin gets its office-scoped defaults only when the role is first
 *   created, so later edits made in the role editor are never overwritten.
 */
class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $system = Role::firstOrNew(['title' => Role::SYSTEM_ADMINISTRATOR]);
        $system->description = 'Manages the whole system. Has every permission.';
        $system->is_system = true;
        $system->save();
        $system->permissions()->sync(Permission::pluck('id'));

        $admin = Role::firstOrNew(['title' => Role::ADMIN]);
        $isNew = ! $admin->exists;
        if ($isNew) {
            $admin->description = 'Office administrator: dashboard, feedback and reports for their own office.';
            $admin->is_system = false;
            $admin->save();
            $admin->permissions()->sync(
                Permission::whereIn('title', PermissionCatalog::ADMIN_DEFAULTS)->pluck('id')
            );
        }
    }
}
