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
        ->assertJsonPath('meta.total', 36)
        ->assertJsonPath('data.0.code', 'OG')
        ->assertJsonPath('data.0.services_count', 7)
        ->assertJsonPath('data.0.active_services_count', 7)
        ->assertJsonPath('data.35.code', 'BeGH')
        ->assertJsonPath('data.35.services_count', 41);
});

it('searches code and name and filters by status', function () {
    Office::where('code', 'OSMP')->update(['is_active' => false]);

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
    $office = Office::where('code', 'OSMP')->firstOrFail();

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
