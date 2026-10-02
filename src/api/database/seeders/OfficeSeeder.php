<?php

namespace Database\Seeders;

use App\Models\Office;
use Illuminate\Database\Seeder;

/**
 * Creates missing offices from data/offices.php. Safe to re-run: existing
 * offices (matched by code) are left as edited in the Offices screen,
 * including their parent. Parents come first in the file.
 */
class OfficeSeeder extends Seeder
{
    public function run(): void
    {
        foreach (require __DIR__.'/data/offices.php' as $position => $row) {
            [$code, $slug, $name] = $row;
            $parentCode = $row[3] ?? null;

            Office::firstOrCreate(['code' => $code], [
                'parent_id' => $parentCode !== null ? Office::where('code', $parentCode)->value('id') : null,
                'slug' => $slug,
                'name' => $name,
                'is_active' => true,
                'sort_order' => $position + 1,
            ]);
        }
    }
}
