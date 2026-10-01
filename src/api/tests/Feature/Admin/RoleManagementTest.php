<?php

use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;

beforeEach(function () {
    $this->actingAs(systemAdministrator());
    $this->system = Role::where('title', 'System Administrator')->firstOrFail();
});

function permissionIds(string ...$titles): array
{
    return Permission::whereIn('title', $titles)->pluck('id')->all();
}

it('lists roles with permission and user counts, System Administrator first', function () {
    $this->getJson('/api/v1/admin/roles')
        ->assertOk()
        ->assertJsonPath('data.0.title', 'System Administrator')
        ->assertJsonPath('data.0.permissions_count', Permission::count())
        ->assertJsonPath('data.0.users_count', 1)
        ->assertJsonPath('data.1.title', 'Admin');
});

it('creates a role with permissions', function () {
    $this->postJson('/api/v1/admin/roles', [
        'title' => 'Encoder',
        'description' => 'Encodes paper forms',
        'permission_ids' => permissionIds('feedback.view', 'feedback.encode'),
    ])->assertCreated()
        ->assertJsonPath('data.title', 'Encoder')
        ->assertJsonPath('data.is_system', false)
        ->assertJsonCount(2, 'data.permissions');
});

it('cannot create a system role through the API', function () {
    $id = $this->postJson('/api/v1/admin/roles', ['title' => 'Sneaky', 'is_system' => true])->json('data.id');

    expect(Role::find($id)->is_system)->toBeFalse();
});

it('validates role titles', function (array $payload, string $field) {
    $this->postJson('/api/v1/admin/roles', $payload)->assertJsonValidationErrors($field);
})->with([
    'missing' => [['title' => ''], 'title'],
    'too long' => [['title' => str_repeat('r', 101)], 'title'],
    'duplicate' => [['title' => 'Admin'], 'title'],
    'unknown permission' => [['title' => 'X', 'permission_ids' => [99999]], 'permission_ids.0'],
]);

it('renames and describes a role', function () {
    $role = Role::factory()->create();

    $this->putJson("/api/v1/admin/roles/{$role->id}", ['title' => 'Reviewer', 'description' => 'Reads reports'])
        ->assertOk()
        ->assertJsonPath('data.title', 'Reviewer');
});

it('will not rename the System Administrator role', function () {
    $this->putJson("/api/v1/admin/roles/{$this->system->id}", ['title' => 'Super User'])
        ->assertJsonValidationErrors('title');
});

it('allows editing the System Administrator description', function () {
    $this->putJson("/api/v1/admin/roles/{$this->system->id}", ['description' => 'Runs the system'])
        ->assertOk();
});

it('will not change System Administrator permissions', function () {
    $this->putJson("/api/v1/admin/roles/{$this->system->id}/permissions", ['permission_ids' => []])
        ->assertJsonValidationErrors('permission_ids');

    expect($this->system->permissions()->count())->toBe(Permission::count());
});

it('replaces a role\'s permissions and records the change', function () {
    $role = Role::factory()->create();
    $role->permissions()->sync(permissionIds('reports.view'));

    $this->putJson("/api/v1/admin/roles/{$role->id}/permissions", ['permission_ids' => permissionIds('reports.view', 'reports.export')])
        ->assertOk()
        ->assertJsonCount(2, 'data.permissions');

    $entry = AuditLog::where('event', 'permissions_synced')->where('auditable_id', (string) $role->id)->firstOrFail();
    expect($entry->old_values)->toBe(['permissions' => ['reports.view']])
        ->and($entry->new_values)->toBe(['permissions' => ['reports.export', 'reports.view']]);
});

it('does not record a sync that changes nothing', function () {
    $role = Role::factory()->create();
    $role->permissions()->sync(permissionIds('reports.view'));

    $this->putJson("/api/v1/admin/roles/{$role->id}/permissions", ['permission_ids' => permissionIds('reports.view')])->assertOk();

    expect(auditCount('permissions_synced', (string) $role->id))->toBe(0);
});

it('takes effect on the user\'s next request', function () {
    $role = Role::factory()->create();
    $user = User::factory()->create();
    $user->roles()->attach($role);

    $this->actingAs($user)->getJson('/api/v1/admin/permissions')->assertForbidden();

    $role->permissions()->sync(permissionIds('permissions.view'));
    $this->actingAs($user->fresh())->getJson('/api/v1/admin/permissions')->assertOk();
});

it('deletes an unused role', function () {
    $role = Role::factory()->create();

    $this->deleteJson("/api/v1/admin/roles/{$role->id}")->assertNoContent();
    $this->assertModelMissing($role);
});

it('will not delete a role that is still assigned', function () {
    $role = Role::factory()->create();
    User::factory()->create()->roles()->attach($role);

    $this->deleteJson("/api/v1/admin/roles/{$role->id}")->assertJsonValidationErrors('role');
});

it('will not delete the System Administrator role', function () {
    $this->deleteJson("/api/v1/admin/roles/{$this->system->id}")->assertJsonValidationErrors('role');
});
