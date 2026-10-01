<?php

use App\Models\User;
use Database\Factories\UserFactory;
use Illuminate\Support\Facades\RateLimiter;
use PragmaRX\Google2FA\Google2FA;

beforeEach(function () {
    RateLimiter::clear('login');
});

function currentCode(User $user): string
{
    return app(Google2FA::class)->getCurrentOtp(decrypt($user->two_factor_secret));
}

it('asks for a code instead of signing in when two-factor is on', function () {
    $user = User::factory()->withTwoFactor()->create();

    $this->postJson('/api/login', ['email' => $user->email, 'password' => UserFactory::PASSWORD])
        ->assertOk()
        ->assertExactJson(['two_factor' => true]);

    $this->assertGuest();
});

it('completes sign-in with a valid authenticator code', function () {
    $user = User::factory()->withTwoFactor()->create();
    $this->postJson('/api/login', ['email' => $user->email, 'password' => UserFactory::PASSWORD]);

    $this->postJson('/api/two-factor-challenge', ['code' => currentCode($user)])
        ->assertNoContent();

    $this->assertAuthenticatedAs($user);
});

it('rejects a wrong code and records the failure', function () {
    $user = User::factory()->withTwoFactor()->create();
    $this->postJson('/api/login', ['email' => $user->email, 'password' => UserFactory::PASSWORD]);

    $this->postJson('/api/two-factor-challenge', ['code' => '000000'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('code');

    $this->assertGuest();
    expect(auditCount('two_factor_failed', $user->id))->toBe(1);
});

it('accepts a recovery code once', function () {
    $user = User::factory()->withTwoFactor()->create();
    $this->postJson('/api/login', ['email' => $user->email, 'password' => UserFactory::PASSWORD]);

    $this->postJson('/api/two-factor-challenge', ['recovery_code' => 'recovery-code-1'])->assertNoContent();
    $this->assertAuthenticatedAs($user);

    expect(json_decode(decrypt($user->fresh()->two_factor_recovery_codes), true))->not->toContain('recovery-code-1');
});

it('cannot reach the challenge without first passing the password step', function () {
    $this->postJson('/api/two-factor-challenge', ['code' => '123456'])->assertUnprocessable();
    $this->assertGuest();
});

it('lets a System Administrator without two-factor see their profile but not admin pages', function () {
    $user = User::factory()->withRole('System Administrator')->create();
    $this->actingAs($user);

    $this->getJson('/api/v1/me')
        ->assertOk()
        ->assertJsonPath('two_factor_required', true)
        ->assertJsonPath('two_factor_enabled', false);

    $this->getJson('/api/v1/admin/users')
        ->assertForbidden()
        ->assertJsonPath('code', 'two_factor_required');
});

it('does not require two-factor for the Admin role', function () {
    $user = userWithPermissions(['users.view']);
    $this->actingAs($user);

    $this->getJson('/api/v1/me')->assertJsonPath('two_factor_required', false);
    $this->getJson('/api/v1/admin/users')->assertOk();
});

it('asks for the password again before turning two-factor on', function () {
    $this->actingAs(User::factory()->create());

    $this->postJson('/api/user/two-factor-authentication')->assertStatus(423);
});

it('turns two-factor on after password confirmation and records it once confirmed', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->postJson('/api/user/confirm-password', ['password' => UserFactory::PASSWORD])->assertCreated();
    $this->postJson('/api/user/two-factor-authentication')->assertOk();
    $this->getJson('/api/user/two-factor-qr-code')->assertOk()->assertJsonStructure(['svg', 'url']);

    $this->postJson('/api/user/confirmed-two-factor-authentication', ['code' => currentCode($user->fresh())])
        ->assertOk();

    expect($user->fresh()->hasEnabledTwoFactor())->toBeTrue()
        ->and(auditCount('two_factor_enabled', $user->id))->toBe(1);
});
