<?php

use App\Models\AuditLog;
use App\Models\User;
use Database\Factories\UserFactory;
use Illuminate\Support\Facades\RateLimiter;

beforeEach(function () {
    RateLimiter::clear('login');
});

it('signs in with the right email and password', function () {
    $user = User::factory()->create(['email' => 'clerk@benguet.gov.ph']);

    $this->postJson('/api/login', ['email' => 'clerk@benguet.gov.ph', 'password' => UserFactory::PASSWORD])
        ->assertOk()
        ->assertExactJson(['two_factor' => false]);

    $this->assertAuthenticatedAs($user);
    expect($user->fresh()->last_login_at)->not->toBeNull();
});

it('matches the email without regard to letter case', function () {
    User::factory()->create(['email' => 'clerk@benguet.gov.ph']);

    $this->postJson('/api/login', ['email' => 'Clerk@Benguet.GOV.ph', 'password' => UserFactory::PASSWORD])
        ->assertOk();
});

it('writes exactly one login audit entry per sign-in', function () {
    $user = User::factory()->create();

    $this->postJson('/api/login', ['email' => $user->email, 'password' => UserFactory::PASSWORD])->assertOk();

    expect(auditCount('login', $user->id))->toBe(1);
});

it('rejects a wrong password without revealing whether the account exists', function () {
    $user = User::factory()->create();

    $wrong = $this->postJson('/api/login', ['email' => $user->email, 'password' => 'Not-The-Password-1!'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('email');

    $unknown = $this->postJson('/api/login', ['email' => 'nobody@benguet.gov.ph', 'password' => 'Not-The-Password-1!'])
        ->assertUnprocessable();

    expect($wrong->json('errors.email'))->toBe($unknown->json('errors.email'));
    $this->assertGuest();
});

it('records failed sign-ins with the email but never the password', function () {
    $this->postJson('/api/login', ['email' => 'someone@benguet.gov.ph', 'password' => 'Secret-Guess-99!'])
        ->assertUnprocessable();

    $entry = AuditLog::where('event', 'login_failed')->latest('id')->firstOrFail();

    expect($entry->new_values)->toBe(['email' => 'someone@benguet.gov.ph'])
        ->and(json_encode($entry->getAttributes()))->not->toContain('Secret-Guess-99!');
});

it('refuses deactivated accounts', function () {
    $user = User::factory()->inactive()->create();

    $this->postJson('/api/login', ['email' => $user->email, 'password' => UserFactory::PASSWORD])
        ->assertUnprocessable();

    $this->assertGuest();
});

it('refuses deleted accounts', function () {
    $user = User::factory()->create();
    $user->delete();

    $this->postJson('/api/login', ['email' => $user->email, 'password' => UserFactory::PASSWORD])
        ->assertUnprocessable();

    $this->assertGuest();
});

it('requires both email and password', function () {
    $this->postJson('/api/login', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email', 'password']);
});

it('locks the email and IP out after five failed attempts in a minute', function () {
    $user = User::factory()->create();

    foreach (range(1, 5) as $attempt) {
        $this->postJson('/api/login', ['email' => $user->email, 'password' => "Wrong-{$attempt}-Password!"])
            ->assertUnprocessable();
    }

    $this->postJson('/api/login', ['email' => $user->email, 'password' => UserFactory::PASSWORD])
        ->assertTooManyRequests();
});

it('signs out and records the logout', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->postJson('/api/logout')->assertNoContent();

    $this->assertGuest('web');
    expect(auditCount('logout', $user->id))->toBe(1);
});

it('does not offer self-registration or email password reset', function (string $uri) {
    $this->postJson($uri, [])->assertNotFound();
})->with(['/api/register', '/api/forgot-password', '/api/reset-password']);
