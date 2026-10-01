<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Support\PermissionCatalog;
use Illuminate\Database\Seeder;

/**
 * Upserts the permission catalog by title. Safe to re-run: existing rows
 * keep their id and role assignments; descriptions follow the catalog.
 */
class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        foreach (PermissionCatalog::PERMISSIONS as $title => $description) {
            $permission = Permission::firstOrNew(['title' => $title]);
            $permission->description = $description;
            $permission->is_protected = true;
            $permission->save();
        }
    }
}
