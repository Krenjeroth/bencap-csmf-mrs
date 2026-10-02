<?php

namespace Database\Seeders;

use App\Models\Office;
use Illuminate\Database\Seeder;

/**
 * Creates missing offices from data/offices.php. Safe to re-run: existing
 * offices (matched by code) are left as edited in the Offices screen.
 */
class OfficeSeeder extends Seeder
{
    public function run(): void
    {
        foreach (require __DIR__.'/data/offices.php' as $position => [$code, $slug, $name]) {
            Office::firstOrCreate(['code' => $code], [
                'slug' => $slug,
                'name' => $name,
                'is_active' => true,
                'sort_order' => $position + 1,
            ]);
        }
    }
}
