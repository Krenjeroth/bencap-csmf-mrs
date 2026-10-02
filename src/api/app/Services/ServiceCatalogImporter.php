<?php

namespace App\Services;

use App\Models\Office;
use App\Models\Service;
use App\Models\ServiceType;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Loads a Citizen's Charter services list (database/seeders/data/services-YYYY.json
 * format) into the services table. Safe to re-run:
 *
 * - a service already present for that office, name and year is left as it is,
 *   so changes made in the Services screen (type, order, active) survive;
 * - new services are added with the type given in the file;
 * - with $retirePrevious, active services from earlier charter years are
 *   deactivated (the yearly charter replacement, a Standard Change).
 */
class ServiceCatalogImporter
{
    /**
     * @param  array{charter_year?: int, services: list<array{office_code: string, name: string, service_type?: string}>}  $data
     * @return array{year: int, added: int, unchanged: int, retired: int, added_names: list<string>}
     */
    public function import(array $data, ?int $year = null, bool $dryRun = false, bool $retirePrevious = false): array
    {
        $year ??= (int) ($data['charter_year'] ?? 0);
        if ($year < 2000 || $year > 2100) {
            throw new InvalidArgumentException('The charter year must be between 2000 and 2100.');
        }

        $rows = $data['services'] ?? null;
        if (! is_array($rows) || $rows === []) {
            throw new InvalidArgumentException('The file has no "services" list.');
        }

        $offices = Office::pluck('id', 'code');
        $types = ServiceType::pluck('id', 'type');
        $errors = [];
        foreach ($rows as $i => $row) {
            $n = $i + 1;
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '' || mb_strlen($name) > 255) {
                $errors[] = "Row {$n}: the name must be 1 to 255 characters.";
            }
            if (! isset($offices[$row['office_code'] ?? ''])) {
                $errors[] = "Row {$n}: unknown office code \"".($row['office_code'] ?? '').'".';
            }
            if (! isset($types[$row['service_type'] ?? ServiceType::EXTERNAL])) {
                $errors[] = "Row {$n}: unknown service type \"".($row['service_type'] ?? '').'".';
            }
        }
        if ($errors !== []) {
            throw new InvalidArgumentException(implode("\n", $errors));
        }

        $result = ['year' => $year, 'added' => 0, 'unchanged' => 0, 'retired' => 0, 'added_names' => []];

        $run = function () use ($rows, $year, $offices, $types, $retirePrevious, $dryRun, &$result) {
            $position = [];
            foreach ($rows as $row) {
                $officeId = $offices[$row['office_code']];
                $name = trim($row['name']);
                $position[$officeId] = ($position[$officeId] ?? 0) + 1;

                $exists = Service::where('office_id', $officeId)->where('name', $name)->where('charter_year', $year)->exists();
                if ($exists) {
                    $result['unchanged']++;

                    continue;
                }

                $result['added']++;
                $result['added_names'][] = "{$row['office_code']}: {$name}";
                if (! $dryRun) {
                    Service::create([
                        'office_id' => $officeId,
                        'service_type_id' => $types[$row['service_type'] ?? ServiceType::EXTERNAL],
                        'name' => $name,
                        'charter_year' => $year,
                        'is_active' => true,
                        'sort_order' => $position[$officeId],
                    ]);
                }
            }

            if ($retirePrevious) {
                $old = Service::active()->where('charter_year', '<', $year);
                $result['retired'] = $old->count();
                if (! $dryRun) {
                    $old->update(['is_active' => false]);
                }
            }
        };

        $dryRun ? $run() : DB::transaction($run);

        return $result;
    }

    /** @return array{charter_year?: int, services: list<array<string, string>>} */
    public function readFile(string $path): array
    {
        if (! is_file($path)) {
            throw new InvalidArgumentException("File not found: {$path}");
        }
        $data = json_decode((string) file_get_contents($path), true);
        if (! is_array($data)) {
            throw new InvalidArgumentException('The file is not valid JSON.');
        }

        return $data;
    }
}
