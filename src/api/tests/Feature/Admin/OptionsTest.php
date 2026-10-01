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
