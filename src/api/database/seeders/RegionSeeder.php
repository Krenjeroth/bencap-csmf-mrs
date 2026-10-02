<?php

namespace Database\Seeders;

use App\Models\Region;
use Illuminate\Database\Seeder;

/**
 * Region of residence options, verbatim from the tally sheet (xlsx C10–C15),
 * plus "Did not specify" (playbook Q9). These look like office levels rather
 * than regions; whether the 17 real regions are wanted is still open (Q9).
 * Safe to re-run: existing rows (matched by name) are left as edited.
 */
class RegionSeeder extends Seeder
{
    /** @var list<string> */
    public const NAMES = [
        'Central Office',
        'Regional Office 1',
        'Regional Office CAR',
        'Regional Office 2',
        'Regional Office 3',
        'Regional Office NCR',
        'Did not specify',
    ];

    public function run(): void
    {
        foreach (self::NAMES as $position => $name) {
            Region::firstOrCreate(['name' => $name], ['sort_order' => $position + 1, 'is_active' => true]);
        }
    }
}
