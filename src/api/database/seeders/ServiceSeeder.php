<?php

namespace Database\Seeders;

use App\Services\ServiceCatalogImporter;
use Illuminate\Database\Seeder;

/**
 * Loads the 2026 Citizen's Charter services (data/services-2026.json).
 * Safe to re-run; see ServiceCatalogImporter.
 */
class ServiceSeeder extends Seeder
{
    public function run(ServiceCatalogImporter $importer): void
    {
        $importer->import($importer->readFile(__DIR__.'/data/services-2026.json'));
    }
}
