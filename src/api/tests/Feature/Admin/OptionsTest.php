<?php

use App\Models\Permission;

it('lists roles for pickers, System Administrator first', function () {
    $this->actingAs(userWithPermissions(['users.view']));

    $this->getJson('/api/v1/admin/role-options')
        ->assertOk()
        ->assertExactJsonStructure(['data' => ['*' => ['id', 'title', 'is_system']]])
        ->assertJsonPath('data.0.title', 'System Administrator')
        ->assertJsonPath('data.0.is_system', true)
        ->assertJsonPath('data.1.title', 'Admin');
});

it('lists every permission for the role editor', function () {
    $this->actingAs(userWithPermissions(['roles.view']));

    $this->getJson('/api/v1/admin/permission-options')
        ->assertOk()
        ->assertJsonCount(Permission::count(), 'data')
        ->assertJsonFragment(['title' => 'users.create', 'resource' => 'users']);
});

it('lists offices for pickers to anyone who may view users, offices or services', function (string $permission) {
    $this->actingAs(userWithPermissions([$permission]));

    $this->getJson('/api/v1/admin/office-options')
        ->assertOk()
        ->assertJsonCount(36, 'data')
        ->assertJsonPath('data.0.code', 'OG');
})->with(['users.view', 'offices.view', 'services.view']);

it('refuses office options without any of those permissions, or when deactivated', function () {
    $this->actingAs(userWithPermissions(['roles.view']));
    $this->getJson('/api/v1/admin/office-options')->assertForbidden();

    $this->actingAs(userWithPermissions(['users.view'], ['is_active' => false]));
    $this->getJson('/api/v1/admin/office-options')->assertForbidden();
});

it('lists service types for the service form', function () {
    $this->actingAs(userWithPermissions(['services.view']));

    $this->getJson('/api/v1/admin/service-type-options')
        ->assertOk()
        ->assertExactJsonStructure(['data' => ['*' => ['id', 'type']]])
        ->assertJsonCount(2, 'data');
});
