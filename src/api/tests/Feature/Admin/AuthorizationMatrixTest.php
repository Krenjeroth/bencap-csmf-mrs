<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;

/*
 * Every admin endpoint × every kind of caller (ADR 0004):
 * guest → 401; signed in without the permission → 403;
 * with exactly that permission → allowed; System Administrator → allowed;
 * deactivated account holding the permission → refused.
 */

dataset('admin endpoints', [
    'role options' => ['GET', '/api/v1/admin/role-options', 'users.view'],
    'permission options' => ['GET', '/api/v1/admin/permission-options', 'roles.view'],
    'list users' => ['GET', '/api/v1/admin/users', 'users.view'],
    'create user' => ['POST', '/api/v1/admin/users', 'users.create'],
    'show user' => ['GET', '/api/v1/admin/users/{user}', 'users.view'],
    'update user' => ['PUT', '/api/v1/admin/users/{user}', 'users.update'],
    'delete user' => ['DELETE', '/api/v1/admin/users/{user}', 'users.delete'],
    'sync user roles' => ['PUT', '/api/v1/admin/users/{user}/roles', 'users.update'],
    'reset password' => ['POST', '/api/v1/admin/users/{user}/reset-password', 'users.update'],
    'list roles' => ['GET', '/api/v1/admin/roles', 'roles.view'],
    'create role' => ['POST', '/api/v1/admin/roles', 'roles.create'],
    'show role' => ['GET', '/api/v1/admin/roles/{role}', 'roles.view'],
    'update role' => ['PUT', '/api/v1/admin/roles/{role}', 'roles.update'],
    'delete role' => ['DELETE', '/api/v1/admin/roles/{role}', 'roles.delete'],
    'sync role permissions' => ['PUT', '/api/v1/admin/roles/{role}/permissions', 'roles.update'],
    'list permissions' => ['GET', '/api/v1/admin/permissions', 'permissions.view'],
    'create permission' => ['POST', '/api/v1/admin/permissions', 'permissions.create'],
    'show permission' => ['GET', '/api/v1/admin/permissions/{permission}', 'permissions.view'],
    'update permission' => ['PUT', '/api/v1/admin/permissions/{permission}', 'permissions.update'],
    'delete permission' => ['DELETE', '/api/v1/admin/permissions/{permission}', 'permissions.delete'],
]);

function resolveUri(string $uri): string
{
    return strtr($uri, [
        '{user}' => User::factory()->create()->id,
        '{role}' => (string) Role::factory()->create()->id,
        '{permission}' => (string) Permission::factory()->create()->id,
    ]);
}

it('requires sign-in', function (string $method, string $uri) {
    $this->json($method, resolveUri($uri))->assertUnauthorized();
})->with('admin endpoints');

it('refuses a signed-in user without the permission', function (string $method, string $uri) {
    $this->actingAs(User::factory()->create());

    $this->json($method, resolveUri($uri))->assertForbidden();
})->with('admin endpoints');

it('refuses a user holding every permission except this one', function (string $method, string $uri, string $permission) {
    $all = Permission::where('title', '!=', $permission)->pluck('title')->all();
    $this->actingAs(userWithPermissions($all));

    $this->json($method, resolveUri($uri))->assertForbidden();
})->with('admin endpoints');

it('allows a user with exactly this permission', function (string $method, string $uri, string $permission) {
    $this->actingAs(userWithPermissions([$permission]));

    $status = $this->json($method, resolveUri($uri))->status();

    expect($status)->not->toBeIn([401, 403]);
})->with('admin endpoints');

it('allows a System Administrator', function (string $method, string $uri) {
    $this->actingAs(systemAdministrator());

    expect($this->json($method, resolveUri($uri))->status())->not->toBeIn([401, 403]);
})->with('admin endpoints');

it('refuses a deactivated account even with the permission', function (string $method, string $uri, string $permission) {
    $this->actingAs(userWithPermissions([$permission], ['is_active' => false]));

    $this->json($method, resolveUri($uri))->assertForbidden();
})->with('admin endpoints');

it('gives the Admin role read access to master data but not to accounts', function () {
    $this->actingAs(User::factory()->admin()->create());

    $this->getJson('/api/v1/admin/users')->assertForbidden();
    $this->getJson('/api/v1/admin/roles')->assertForbidden();
    $this->getJson('/api/v1/admin/permissions')->assertForbidden();
});
