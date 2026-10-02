<?php

use App\Models\Office;
use App\Models\User;

function officeId(string $code): int
{
    return Office::where('code', $code)->value('id');
}

it('assigns an office when creating and editing a user', function () {
    $this->actingAs(systemAdministrator());

    $id = $this->postJson('/api/v1/admin/users', ['name' => 'PHO Focal Person', 'email' => 'focal@benguet.gov.ph', 'office_id' => officeId('PHO'), 'role_ids' => []])
        ->assertCreated()
        ->assertJsonPath('data.office.code', 'PHO')
        ->json('data.id');

    $this->putJson("/api/v1/admin/users/{$id}", ['office_id' => officeId('PTO')])->assertJsonPath('data.office.code', 'PTO');
    $this->putJson("/api/v1/admin/users/{$id}", ['office_id' => null])->assertJsonPath('data.office', null);
});

it('rejects an unknown office', function () {
    $this->actingAs(systemAdministrator());

    $this->postJson('/api/v1/admin/users', ['name' => 'X', 'email' => 'x@benguet.gov.ph', 'office_id' => 99999, 'role_ids' => []])
        ->assertJsonValidationErrors('office_id');
});

it('filters users by office', function () {
    $this->actingAs(systemAdministrator());
    User::factory()->count(2)->create(['office_id' => officeId('PHO')]);

    $this->getJson('/api/v1/admin/users?office_id='.officeId('PHO'))->assertJsonPath('meta.total', 2);
});

it('shows the user’s office on /me', function () {
    $this->actingAs(User::factory()->admin()->create(['office_id' => officeId('KDH')]));

    $this->getJson('/api/v1/me')->assertJsonPath('office.code', 'KDH');
});

it('stops an office-limited user from placing accounts in another office', function () {
    $this->actingAs(userWithPermissions(['users.view', 'users.create', 'users.update'], ['office_id' => officeId('PHO')]));

    $this->postJson('/api/v1/admin/users', ['name' => 'X', 'email' => 'x@benguet.gov.ph', 'office_id' => officeId('PTO'), 'role_ids' => []])
        ->assertJsonValidationErrors('office_id');
    $this->postJson('/api/v1/admin/users', ['name' => 'X', 'email' => 'x@benguet.gov.ph', 'office_id' => null, 'role_ids' => []])
        ->assertJsonValidationErrors('office_id');
    $this->postJson('/api/v1/admin/users', ['name' => 'Y', 'email' => 'y@benguet.gov.ph', 'office_id' => officeId('PHO'), 'role_ids' => []])
        ->assertCreated();
});

it('will not delete an office that still has users', function () {
    $this->actingAs(systemAdministrator());
    $office = Office::factory()->create();
    User::factory()->create(['office_id' => $office->id]);

    $this->deleteJson("/api/v1/admin/offices/{$office->id}")->assertJsonValidationErrors('office');
});
