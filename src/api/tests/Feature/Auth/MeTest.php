<?php

use App\Models\User;
use App\Support\PermissionCatalog;

it('requires sign-in', function () {
    $this->getJson('/api/v1/me')->assertUnauthorized();
});

it('returns the user with roles and permissions', function () {
    $user = User::factory()->admin()->create(['name' => 'Office Admin']);
    $this->actingAs($user);

    $this->getJson('/api/v1/me')
        ->assertOk()
        ->assertJsonPath('id', $user->id)
        ->assertJsonPath('name', 'Office Admin')
        ->assertJsonPath('is_system_administrator', false)
        ->assertJsonPath('roles.0.title', 'Admin')
        ->assertJsonPath('permissions', collect(PermissionCatalog::ADMIN_DEFAULTS)->sort()->values()->all());
});

it('gives a System Administrator every catalog permission', function () {
    $this->actingAs(systemAdministrator());

    $permissions = $this->getJson('/api/v1/me')->assertOk()->json('permissions');

    expect($permissions)->toEqualCanonicalizing(PermissionCatalog::titles());
});

it('uses a UUID as the user id', function () {
    $this->actingAs($user = User::factory()->create());

    expect($this->getJson('/api/v1/me')->json('id'))->toBe($user->id)->toBeUuid();
});

it('never exposes the password or two-factor secrets', function () {
    $this->actingAs(User::factory()->withTwoFactor()->create());

    $data = $this->getJson('/api/v1/me')->json();

    expect(array_keys($data))->not->toContain('password')
        ->not->toContain('two_factor_secret')
        ->not->toContain('two_factor_recovery_codes')
        ->not->toContain('remember_token');
});

it('answers without a "data" wrapper, because nuxt-auth-sanctum stores the body as the user', function () {
    $this->actingAs(User::factory()->create());

    $body = $this->getJson('/api/v1/me')->json();

    expect($body)->not->toHaveKey('data')->toHaveKeys(['id', 'email', 'roles', 'permissions']);
});
