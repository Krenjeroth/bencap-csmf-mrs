<?php

use App\Models\Office;
use App\Models\Service;
use App\Models\User;

beforeEach(function () {
    $this->actingAs(systemAdministrator());
});

it('lists offices in charter order with service and user counts', function () {
    $this->getJson('/api/v1/admin/offices?per_page=100')
        ->assertOk()
        ->assertJsonPath('meta.total', 34)
        ->assertJsonPath('data.0.code', 'OG')
        ->assertJsonPath('data.0.services_count', 7)
        ->assertJsonPath('data.0.active_services_count', 7)
        ->assertJsonPath('data.33.code', 'BeGH')
        ->assertJsonPath('data.33.services_count', 41);
});

it('searches code and name and filters by status', function () {
    Office::where('code', 'OSSP')->update(['is_active' => false]);

    $this->getJson('/api/v1/admin/offices?q=hospital')->assertJsonPath('meta.total', 6);
    $this->getJson('/api/v1/admin/offices?q=PESO')->assertJsonPath('data.0.code', 'OG-PESO');
    $this->getJson('/api/v1/admin/offices?status=inactive')->assertJsonPath('meta.total', 1);
});

it('creates an office and builds the slug from the code when left empty', function () {
    $this->postJson('/api/v1/admin/offices', ['code' => 'OG-LEDIPO', 'name' => 'Local Economic Development and Investment Promotion Office'])
        ->assertCreated()
        ->assertJsonPath('data.slug', 'og-ledipo')
        ->assertJsonPath('data.is_active', true);

    expect(auditCount('created', (string) Office::where('code', 'OG-LEDIPO')->value('id'), 'Office'))->toBe(1);
});

it('validates offices', function (array $payload, string $field) {
    $this->postJson('/api/v1/admin/offices', [...['code' => 'NEW', 'name' => 'New Office'], ...$payload])
        ->assertJsonValidationErrors($field);
})->with([
    'code missing' => [['code' => ''], 'code'],
    'code taken' => [['code' => 'PHO'], 'code'],
    'code 31 chars' => [['code' => str_repeat('C', 31)], 'code'],
    'name missing' => [['name' => ''], 'name'],
    'name 151 chars' => [['name' => str_repeat('n', 151)], 'name'],
    'slug taken' => [['slug' => 'pho'], 'slug'],
]);

it('names the taken guest form address, also when it was made from the code', function () {
    $this->postJson('/api/v1/admin/offices', ['code' => 'P-H-O', 'name' => 'Other', 'slug' => 'pho'])
        ->assertJsonValidationErrors(['slug' => 'Another office already uses the guest form address /f/pho.']);

    // A new code, but its made-up address og-it is OG-IT's.
    $this->postJson('/api/v1/admin/offices', ['code' => 'OG IT', 'name' => 'Other'])
        ->assertJsonValidationErrors(['slug' => 'Another office already uses the guest form address /f/og-it.']);
});

it('accepts the longest allowed code and name', function () {
    $this->postJson('/api/v1/admin/offices', ['code' => str_repeat('C', 30), 'name' => str_repeat('n', 150)])->assertCreated();
});

it('normalises a typed slug to lower-case dashes', function () {
    $this->postJson('/api/v1/admin/offices', ['code' => 'X1', 'name' => 'X', 'slug' => 'My Office Slug'])
        ->assertCreated()
        ->assertJsonPath('data.slug', 'my-office-slug');
});

it('renames and deactivates an office', function () {
    $office = Office::where('code', 'OSSP')->firstOrFail();

    $this->putJson("/api/v1/admin/offices/{$office->id}", ['name' => 'Sangguniang Panlalawigan Members', 'is_active' => false])
        ->assertOk()
        ->assertJsonPath('data.name', 'Sangguniang Panlalawigan Members')
        ->assertJsonPath('data.is_active', false);
});

it('keeps its own code without a uniqueness error', function () {
    $office = Office::where('code', 'PHO')->firstOrFail();

    $this->putJson("/api/v1/admin/offices/{$office->id}", ['code' => 'PHO'])->assertOk();
});

it('deletes an office nothing refers to', function () {
    $office = Office::factory()->create();

    $this->deleteJson("/api/v1/admin/offices/{$office->id}")->assertNoContent();
    $this->assertModelMissing($office);
});

it('will not delete an office that has services', function () {
    $office = Office::where('code', 'PHO')->firstOrFail();

    $this->deleteJson("/api/v1/admin/offices/{$office->id}")->assertJsonValidationErrors('office');
    $this->assertModelExists($office);
});

it('will not delete an office that has user accounts, even deleted ones', function () {
    $office = Office::factory()->create();
    User::factory()->create(['office_id' => $office->id])->delete();

    $this->deleteJson("/api/v1/admin/offices/{$office->id}")->assertJsonValidationErrors('office');
});

it('counts only active services as active', function () {
    $office = Office::factory()->create();
    Service::factory()->count(2)->create(['office_id' => $office->id]);
    Service::factory()->create(['office_id' => $office->id, 'is_active' => false]);

    $this->getJson("/api/v1/admin/offices?q={$office->code}")
        ->assertJsonPath('data.0.services_count', 3)
        ->assertJsonPath('data.0.active_services_count', 2);
});

describe('office hierarchy', function () {
    it('lists each office with its parent and the number of offices under it', function () {
        $og = Office::where('code', 'OG')->firstOrFail();

        $this->getJson('/api/v1/admin/offices?q=OG-BTS')
            ->assertJsonPath('data.0.parent_id', $og->id)
            ->assertJsonPath('data.0.parent.code', 'OG')
            ->assertJsonPath('data.0.children_count', 0);
        $this->getJson("/api/v1/admin/offices/{$og->id}")
            ->assertJsonPath('data.parent', null)
            ->assertJsonPath('data.children_count', 11);
    });

    it('creates an office under a top-level office', function () {
        $og = Office::where('code', 'OG')->firstOrFail();

        $this->postJson('/api/v1/admin/offices', ['code' => 'OG-NEW', 'name' => 'New Unit', 'parent_id' => $og->id])
            ->assertCreated()
            ->assertJsonPath('data.parent.code', 'OG');
    });

    it('moves an office to the top level and back', function () {
        $bts = Office::where('code', 'OG-BTS')->firstOrFail();
        $pho = Office::where('code', 'PHO')->firstOrFail();

        $this->putJson("/api/v1/admin/offices/{$bts->id}", ['parent_id' => null])
            ->assertOk()->assertJsonPath('data.parent_id', null);
        $this->putJson("/api/v1/admin/offices/{$bts->id}", ['parent_id' => $pho->id])
            ->assertOk()->assertJsonPath('data.parent.code', 'PHO');
    });

    it('refuses a parent that would make the tree deeper or loop', function (string $code, string $parentCode, string $message) {
        $office = Office::where('code', $code)->firstOrFail();
        $parent = Office::where('code', $parentCode)->firstOrFail();

        $this->putJson("/api/v1/admin/offices/{$office->id}", ['parent_id' => $parent->id])
            ->assertJsonValidationErrors(['parent_id' => $message]);
    })->with([
        'itself' => ['PHO', 'PHO', 'An office cannot sit under itself.'],
        'a child office' => ['PHO', 'OG-BTS', 'OG-BTS already sits under another office. Choose a top-level office.'],
        'an office with children' => ['OG', 'PHO', 'Other offices sit under OG, so it must stay a top-level office.'],
    ]);

    it('refuses a parent that does not exist', function () {
        $this->postJson('/api/v1/admin/offices', ['code' => 'X2', 'name' => 'X', 'parent_id' => 999999])
            ->assertJsonValidationErrors('parent_id');
    });

    it('will not delete an office that other offices sit under', function () {
        $parent = Office::factory()->create();
        Office::factory()->create(['parent_id' => $parent->id]);

        $this->deleteJson("/api/v1/admin/offices/{$parent->id}")
            ->assertJsonValidationErrors(['office' => '1 office(s) sit under this office. Move them to another office first.']);
        $this->assertModelExists($parent);
    });

    it('gives pickers each office parent', function () {
        $og = Office::where('code', 'OG')->value('id');

        $options = collect($this->getJson('/api/v1/admin/office-options')->assertOk()->json('data'));

        expect($options->firstWhere('code', 'OG-IT')['parent_id'])->toBe($og)
            ->and($options->firstWhere('code', 'OG')['parent_id'])->toBeNull();
    });
});
