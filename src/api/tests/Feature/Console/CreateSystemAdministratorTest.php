<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('creates a System Administrator from options', function () {
    $this->artisan('csmf:create-sysadmin', [
        '--name' => 'Krenjer Jan J. Sapitola',
        '--email' => 'SysAdmin@Benguet.gov.ph',
        '--password' => 'Strong-Start-2026!',
    ])->expectsOutputToContain('System Administrator created: sysadmin@benguet.gov.ph')
        ->assertSuccessful();

    $user = User::where('email', 'sysadmin@benguet.gov.ph')->firstOrFail();
    expect($user->isSystemAdministrator())->toBeTrue()
        ->and($user->must_change_password)->toBeFalse()
        ->and(Hash::check('Strong-Start-2026!', $user->password))->toBeTrue();
});

it('prompts for the password without echo and checks the confirmation', function () {
    $this->artisan('csmf:create-sysadmin', ['--name' => 'Admin One', '--email' => 'one@benguet.gov.ph'])
        ->expectsQuestion('Password (min 12 chars, upper and lower case, a number and a symbol)', 'Strong-Start-2026!')
        ->expectsQuestion('Confirm password', 'Strong-Start-2026!')
        ->assertSuccessful();

    expect(User::where('email', 'one@benguet.gov.ph')->exists())->toBeTrue();
});

it('stops when the confirmation does not match', function () {
    $this->artisan('csmf:create-sysadmin', ['--name' => 'Admin One', '--email' => 'one@benguet.gov.ph'])
        ->expectsQuestion('Password (min 12 chars, upper and lower case, a number and a symbol)', 'Strong-Start-2026!')
        ->expectsQuestion('Confirm password', 'Different-2026!!')
        ->expectsOutputToContain('The passwords do not match.')
        ->assertFailed();

    expect(User::count())->toBe(0);
});

it('rejects a weak password', function () {
    $this->artisan('csmf:create-sysadmin', ['--name' => 'A', '--email' => 'a@benguet.gov.ph', '--password' => 'short'])
        ->assertFailed();

    expect(User::count())->toBe(0);
});

it('rejects an invalid email', function () {
    $this->artisan('csmf:create-sysadmin', ['--name' => 'A', '--email' => 'not-an-email', '--password' => 'Strong-Start-2026!'])
        ->assertFailed();
});

it('refuses when an active System Administrator already exists', function () {
    systemAdministrator();

    $this->artisan('csmf:create-sysadmin', ['--name' => 'B', '--email' => 'b@benguet.gov.ph', '--password' => 'Strong-Start-2026!'])
        ->expectsOutputToContain('already exists')
        ->assertFailed();
});

it('is safe to run on a fresh database because it seeds roles first', function () {
    Role::query()->delete();

    $this->artisan('csmf:create-sysadmin', ['--name' => 'C', '--email' => 'c@benguet.gov.ph', '--password' => 'Strong-Start-2026!'])
        ->assertSuccessful();
});
