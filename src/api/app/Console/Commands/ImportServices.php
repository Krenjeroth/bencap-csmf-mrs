<?php

namespace App\Console\Commands;

use App\Services\ServiceCatalogImporter;
use Illuminate\Console\Command;
use InvalidArgumentException;

/**
 * Imports a Citizen's Charter services list, e.g. the yearly new edition.
 *
 *   php artisan csmf:import-services database/seeders/data/services-2027.json --dry-run
 *   php artisan csmf:import-services database/seeders/data/services-2027.json --retire-previous
 */
class ImportServices extends Command
{
    protected $signature = 'csmf:import-services
        {file : JSON file in the services-YYYY.json format}
        {--year= : Charter year (defaults to the file\'s charter_year)}
        {--retire-previous : Deactivate active services from earlier charter years}
        {--dry-run : Show what would change without saving}';

    protected $description = 'Import a Citizen\'s Charter services list';

    public function handle(ServiceCatalogImporter $importer): int
    {
        try {
            $data = $importer->readFile((string) $this->argument('file'));
            $year = $this->option('year') !== null ? (int) $this->option('year') : null;
            $result = $importer->import($data, $year, (bool) $this->option('dry-run'), (bool) $this->option('retire-previous'));
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $prefix = $this->option('dry-run') ? 'Would add' : 'Added';
        $this->info("Charter year {$result['year']}: {$prefix} {$result['added']}, unchanged {$result['unchanged']}"
            .($this->option('retire-previous') ? ', '.($this->option('dry-run') ? 'would retire' : 'retired')." {$result['retired']} from earlier years" : '').'.');

        if ($this->option('dry-run') && $result['added_names'] !== []) {
            foreach ($result['added_names'] as $line) {
                $this->line("  + {$line}");
            }
        }

        return self::SUCCESS;
    }
}
