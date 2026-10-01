<?php

use App\Models\Role;
use App\Models\User;
use Database\Factories\UserFactory;
use Illuminate\Support\Facades\Hash;

it('blocks admin pages until a temporary password is changed', function () {
    $user = User::factory()->mustChangePassword()->create();
    $user->roles()->attach(Role::where('title', 'Admin')->first());
    $this->actingAs($user);

    $this->getJson('/api/v1/me')->assertOk()->assertJsonPath('must_change_password', true);

    $this->getJson('/api/v1/admin/roles')
        ->assertForbidden()
        ->assertJsonPath('code', 'password_change_required');
});

it('clears the requirement once the password is changed', function () {
    $user = User::factory()->mustChangePassword()->create();
    $this->actingAs($user);

    $this->putJson('/api/user/password', [
        'current_password' => UserFactory::PASSWORD,
        'password' => 'Brand-New-Pass-2026!',
        'password_confirmation' => 'Brand-New-Pass-2026!',
    ])->assertOk();

    $user->refresh();
    expect($user->must_change_password)->toBeFalse()
        ->and(Hash::check('Brand-New-Pass-2026!', $user->password))->toBeTrue()
        ->and(auditCount('password_changed', $user->id))->toBe(1);
});

it('requires the current password', function () {
    $this->actingAs(User::factory()->create());

    $this->putJson('/api/user/password', [
        'current_password' => 'Wrong-Current-1!',
        'password' => 'Brand-New-Pass-2026!',
        'password_confirmation' => 'Brand-New-Pass-2026!',
    ])->assertUnprocessable()->assertJsonValidationErrors('current_password');
});

it('rejects reusing the current password', function () {
    $this->actingAs(User::factory()->create());

    $this->putJson('/api/user/password', [
        'current_password' => UserFactory::PASSWORD,
        'password' => UserFactory::PASSWORD,
        'password_confirmation' => UserFactory::PASSWORD,
    ])->assertUnprocessable()->assertJsonValidationErrors('password');
});

it('enforces the password policy', function (string $password, bool $valid) {
    $this->actingAs(User::factory()->create());

    $response = $this->putJson('/api/user/password', [
        'current_password' => UserFactory::PASSWORD,
        'password' => $password,
        'password_confirmation' => $password,
    ]);

    $valid ? $response->assertOk() : $response->assertUnprocessable();
})->with([
    '11 characters' => ['Abcdefgh1!x', false],
    '12 characters' => ['Abcdefgh1!xy', true],
    'no symbol' => ['Abcdefgh12xy', false],
    'no number' => ['Abcdefgh!!xy', false],
    'no upper case' => ['abcdefgh1!xy', false],
    'no lower case' => ['ABCDEFGH1!XY', false],
]);

it('requires the confirmation to match', function () {
    $this->actingAs(User::factory()->create());

    $this->putJson('/api/user/password', [
        'current_password' => UserFactory::PASSWORD,
        'password' => 'Brand-New-Pass-2026!',
        'password_confirmation' => 'Different-Pass-2026!',
    ])->assertUnprocessable();
});
