<?php

use App\Models\Office;
use App\Models\Region;
use App\Models\Service;
use App\Models\ServiceType;
use App\Models\SqdQuestion;
use Database\Seeders\DatabaseSeeder;

function writeCatalog(array $data): string
{
    $path = tempnam(sys_get_temp_dir(), 'svc').'.json';
    file_put_contents($path, json_encode($data));

    return $path;
}

describe('seed data', function () {
    it('creates the 36 offices in charter order', function () {
        expect(Office::count())->toBe(36)
            ->and(Office::ordered()->first()->code)->toBe('OG')
            ->and(Office::ordered()->get()->last()->code)->toBe('BeGH')
            ->and(Office::whereIn('code', ['OG-PESO', 'OG-BAC', 'OG-CAO', 'OG-SDO'])->count())->toBe(4);
    });

    it('keeps office codes and names exactly as provided', function () {
        expect(Office::where('code', 'PPDO')->value('name'))->toBe('Provincial Plannning and Develoment Office (PPDO)')
            ->and(Office::where('code', 'OG-Provincial Library')->value('slug'))->toBe('og-library');
    });

    it('loads all 241 services from the 2026 charter, 6 of them Internal', function () {
        expect(Service::count())->toBe(241)
            ->and(Service::where('charter_year', 2026)->count())->toBe(241)
            ->and(Service::whereHas('serviceType', fn ($q) => $q->where('type', 'External'))->count())->toBe(235);

        // Decided 2026-10-02, see docs/citizens-charter-review.md.
        $internal = Service::with('office')
            ->whereHas('serviceType', fn ($q) => $q->where('type', 'Internal'))
            ->get()
            ->map(fn (Service $s) => $s->office->code)
            ->sort()->values()->all();
        expect($internal)->toBe(['OG-Provincial Library', 'OG-Records', 'PAccO', 'PEO', 'PHRMDO', 'PTO']);
    });

    it('gives each office its charter services', function (string $code, int $count) {
        expect(Office::where('code', $code)->firstOrFail()->services()->count())->toBe($count);
    })->with([
        ['OG', 7], ['OG-BAC', 3], ['OG-BTS', 8], ['OG-PDRRMO', 7], ['OVG', 4], ['PBO', 2],
        ['PSWDO', 7], ['PTO', 16], ['PVO', 19], ['IDH', 13], ['KDH', 13], ['NBDH', 14], ['BeGH', 41],
        ['OG-OPA', 0], ['OSMP', 0],
    ]);

    it('writes sub-services as Parent – Service', function () {
        expect(Service::where('name', 'Radiology Department – A.1 X-Ray Procedures: OPD (Paying Patients)')->exists())->toBeTrue()
            ->and(Service::where('name', 'Disaster Response – Heavy Equipment Support Services')->exists())->toBeTrue();
    });

    it('places the twelve OG-* offices under OG and keeps the tree one level deep', function () {
        $og = Office::where('code', 'OG')->firstOrFail();

        expect($og->parent_id)->toBeNull()
            ->and($og->children()->count())->toBe(12)
            ->and(Office::where('code', 'like', 'OG-%')->where('parent_id', '!=', $og->id)->count())->toBe(0)
            ->and(Office::whereNotNull('parent_id')->count())->toBe(12)
            ->and(Office::whereIn('parent_id', Office::whereNotNull('parent_id')->select('id'))->count())->toBe(0);
    });

    it('seeds the region of residence options in tally sheet order', function () {
        expect(Region::ordered()->pluck('name')->all())->toBe([
            'Central Office', 'Regional Office 1', 'Regional Office CAR', 'Regional Office 2',
            'Regional Office 3', 'Regional Office NCR', 'Did not specify',
        ]);
    });

    it('seeds SQD0 to SQD8 verbatim, with SQD0 outside the overall score', function () {
        $questions = SqdQuestion::current()->get();

        expect($questions->pluck('code')->all())->toBe(['SQD0', 'SQD1', 'SQD2', 'SQD3', 'SQD4', 'SQD5', 'SQD6', 'SQD7', 'SQD8'])
            ->and($questions->where('included_in_overall', false)->pluck('code')->values()->all())->toBe(['SQD0'])
            ->and($questions->firstWhere('code', 'SQD6')->statement)->toBe('I feel the office was fair to everyone, or “walang palakasan”, during my transaction.')
            ->and($questions->every(fn (SqdQuestion $q) => mb_strlen($q->statement) <= 255))->toBeTrue();
    });

    it('produces the same data when run twice', function () {
        $counts = fn () => [
            Office::count(), Office::whereNotNull('parent_id')->count(), Service::count(),
            ServiceType::count(), Region::count(), SqdQuestion::count(),
        ];
        $before = $counts();

        $this->seed(DatabaseSeeder::class);

        expect($counts())->toBe($before)->and($before)->toBe([36, 12, 241, 2, 7, 9]);
    });

    it('keeps changes made in the screens when re-run', function () {
        $service = Service::where('name', 'Payment of Approved Vouchers and Payrolls')->firstOrFail();
        $service->update(['service_type_id' => ServiceType::where('type', 'Internal')->value('id'), 'is_active' => false]);
        Office::where('code', 'PHO')->update(['name' => 'Provincial Health Office']);
        Office::where('code', 'OG-BTS')->update(['parent_id' => null]);
        Region::where('name', 'Did not specify')->update(['is_active' => false]);

        $this->seed(DatabaseSeeder::class);

        expect($service->fresh()->serviceType->type)->toBe('Internal')
            ->and($service->fresh()->is_active)->toBeFalse()
            ->and(Office::where('code', 'PHO')->value('name'))->toBe('Provincial Health Office')
            ->and(Office::where('code', 'OG-BTS')->value('parent_id'))->toBeNull()
            ->and(Region::where('name', 'Did not specify')->value('is_active'))->toBeFalse();
    });
});

describe('csmf:import-services', function () {
    it('shows what a new edition would add without saving', function () {
        $path = writeCatalog(['charter_year' => 2027, 'services' => [
            ['office_code' => 'PHO', 'name' => 'Microbiological Water Analysis'],
            ['office_code' => 'PHO', 'name' => 'Rabies Vaccination Drive'],
        ]]);

        $this->artisan('csmf:import-services', ['file' => $path, '--dry-run' => true])
            ->expectsOutputToContain('Would add 2')
            ->expectsOutputToContain('PHO: Rabies Vaccination Drive')
            ->assertSuccessful();

        expect(Service::where('charter_year', 2027)->count())->toBe(0);
    });

    it('imports a new edition and retires the previous year', function () {
        $path = writeCatalog(['charter_year' => 2027, 'services' => [
            ['office_code' => 'PHO', 'name' => 'Microbiological Water Analysis'],
            ['office_code' => 'PTO', 'name' => 'Payment of Approved Vouchers and Payrolls', 'service_type' => 'Internal'],
        ]]);

        $this->artisan('csmf:import-services', ['file' => $path, '--retire-previous' => true])
            ->expectsOutputToContain('Added 2, unchanged 0, retired 241 from earlier years')
            ->assertSuccessful();

        expect(Service::active()->count())->toBe(2)
            ->and(Service::where('charter_year', 2027)->where('name', 'Payment of Approved Vouchers and Payrolls')->first()->serviceType->type)->toBe('Internal')
            ->and(Service::where('charter_year', 2026)->count())->toBe(241);
    });

    it('is safe to run twice', function () {
        $path = writeCatalog(['charter_year' => 2027, 'services' => [['office_code' => 'PHO', 'name' => 'Rabies Vaccination Drive']]]);

        $this->artisan('csmf:import-services', ['file' => $path])->assertSuccessful();
        $this->artisan('csmf:import-services', ['file' => $path])->expectsOutputToContain('Added 0, unchanged 1')->assertSuccessful();
    });

    it('refuses a file with an unknown office and saves nothing', function () {
        $path = writeCatalog(['charter_year' => 2027, 'services' => [
            ['office_code' => 'PHO', 'name' => 'Fine'],
            ['office_code' => 'NOPE', 'name' => 'Broken'],
        ]]);

        $this->artisan('csmf:import-services', ['file' => $path])
            ->expectsOutputToContain('Row 2: unknown office code "NOPE"')
            ->assertFailed();

        expect(Service::where('charter_year', 2027)->count())->toBe(0);
    });

    it('refuses invalid input', function (string $content, string $message) {
        $path = tempnam(sys_get_temp_dir(), 'svc');
        file_put_contents($path, $content);

        $this->artisan('csmf:import-services', ['file' => $path])->expectsOutputToContain($message)->assertFailed();
    })->with([
        'not JSON' => ['{oops', 'not valid JSON'],
        'no services' => ['{"charter_year":2027,"services":[]}', 'no "services" list'],
        'bad year' => ['{"charter_year":1990,"services":[{"office_code":"PHO","name":"X"}]}', 'between 2000 and 2100'],
        'empty name' => ['{"charter_year":2027,"services":[{"office_code":"PHO","name":" "}]}', 'Row 1: the name must be'],
        'unknown type' => ['{"charter_year":2027,"services":[{"office_code":"PHO","name":"X","service_type":"Other"}]}', 'unknown service type'],
    ]);

    it('reports a missing file', function () {
        $this->artisan('csmf:import-services', ['file' => 'does/not/exist.json'])->expectsOutputToContain('File not found')->assertFailed();
    });
});
