<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Reference data only; safe to re-run. No user accounts are seeded: create
 * the first System Administrator with `php artisan csmf:create-sysadmin`.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            OfficeSeeder::class,
            ServiceTypeSeeder::class,
            ServiceSeeder::class,
            RegionSeeder::class,
            SqdQuestionSeeder::class,
        ]);
    }
}
