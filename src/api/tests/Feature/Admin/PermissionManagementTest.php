<?php

use App\Models\Permission;
use App\Models\Role;
use App\Support\PermissionCatalog;

beforeEach(function () {
    $this->actingAs(systemAdministrator());
});

it('lists the catalog with resource names', function () {
    $this->getJson('/api/v1/admin/permissions?per_page=100')
        ->assertOk()
        ->assertJsonPath('meta.total', count(PermissionCatalog::PERMISSIONS))
        ->assertJsonFragment(['title' => 'users.create', 'resource' => 'users', 'is_protected' => true]);
});

it('searches titles and descriptions', function () {
    $this->getJson('/api/v1/admin/permissions?q=kiosk')->assertJsonCount(1, 'data');
});

it('adds a custom permission and grants it to System Administrator', function () {
    $id = $this->postJson('/api/v1/admin/permissions', ['title' => 'Reports.Print', 'description' => 'Print reports'])
        ->assertCreated()
        ->assertJsonPath('data.title', 'reports.print')
        ->assertJsonPath('data.is_protected', false)
        ->json('data.id');

    expect(Role::where('title', 'System Administrator')->first()->permissions()->whereKey($id)->exists())->toBeTrue();
});

it('requires the resource.action form', function (string $title, bool $valid) {
    $response = $this->postJson('/api/v1/admin/permissions', ['title' => $title]);

    $valid ? $response->assertCreated() : $response->assertJsonValidationErrors('title');
})->with([
    ['reports.print', true],
    ['service-types.archive', true],
    ['reports.print_all', true],
    ['reports', false],
    ['reports.', false],
    ['.print', false],
    ['reports.print.now', false],
    ['1reports.print', false],
    ['reports print', false],
]);

it('rejects a duplicate title', function () {
    $this->postJson('/api/v1/admin/permissions', ['title' => 'users.view'])->assertJsonValidationErrors('title');
});

it('edits the description of a catalog permission but not its title', function () {
    $permission = Permission::where('title', 'users.view')->firstOrFail();

    $this->putJson("/api/v1/admin/permissions/{$permission->id}", ['description' => 'See staff accounts'])->assertOk();
    $this->putJson("/api/v1/admin/permissions/{$permission->id}", ['title' => 'people.view'])->assertJsonValidationErrors('title');

    expect($permission->fresh()->title)->toBe('users.view');
});

it('will not delete a catalog permission', function () {
    $permission = Permission::where('title', 'dashboard.view')->firstOrFail();

    $this->deleteJson("/api/v1/admin/permissions/{$permission->id}")->assertJsonValidationErrors('permission');
    $this->assertModelExists($permission);
});

it('renames and deletes a custom permission', function () {
    $permission = Permission::factory()->create(['title' => 'reports.print']);

    $this->putJson("/api/v1/admin/permissions/{$permission->id}", ['title' => 'reports.print-all'])->assertOk();
    $this->deleteJson("/api/v1/admin/permissions/{$permission->id}")->assertNoContent();
    $this->assertModelMissing($permission);
});
