<?php

namespace Database\Seeders;

use App\Models\Region;
use Illuminate\Database\Seeder;

/**
 * Region of residence options (playbook Q9, decided 2026-10-02): the 18
 * Philippine regions, including the Negros Island Region re-created by
 * RA 12000 (2024), with CAR first because most of Benguet's clients live
 * there, then the PSA order, then "Did not specify". The tally sheet's
 * options (Central Office, Regional Office 1 …) were office levels, not
 * places of residence. Safe to re-run: existing rows (matched by name) are
 * left as edited.
 */
class RegionSeeder extends Seeder
{
    /** @var list<string> */
    public const NAMES = [
        'Cordillera Administrative Region (CAR)',
        'National Capital Region (NCR)',
        'Region I – Ilocos Region',
        'Region II – Cagayan Valley',
        'Region III – Central Luzon',
        'Region IV-A – CALABARZON',
        'MIMAROPA Region',
        'Region V – Bicol Region',
        'Region VI – Western Visayas',
        'Negros Island Region (NIR)',
        'Region VII – Central Visayas',
        'Region VIII – Eastern Visayas',
        'Region IX – Zamboanga Peninsula',
        'Region X – Northern Mindanao',
        'Region XI – Davao Region',
        'Region XII – SOCCSKSARGEN',
        'Region XIII – Caraga',
        'Bangsamoro Autonomous Region in Muslim Mindanao (BARMM)',
        'Did not specify',
    ];

    public function run(): void
    {
        foreach (self::NAMES as $position => $name) {
            Region::firstOrCreate(['name' => $name], ['sort_order' => $position + 1, 'is_active' => true]);
        }
    }
}
