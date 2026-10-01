<?php

use App\Models\AuditLog;
use App\Models\User;
use App\Support\AuditLogger;

it('cannot be changed once written', function () {
    $entry = app(AuditLogger::class)->record('login');

    expect(fn () => $entry->update(['event' => 'tampered']))->toThrow(LogicException::class)
        ->and($entry->fresh()->event)->toBe('login');
});

it('cannot be deleted', function () {
    $entry = app(AuditLogger::class)->record('login');

    expect(fn () => $entry->delete())->toThrow(LogicException::class);
    $this->assertModelExists($entry);
});

it('records who, from where and on which record', function () {
    $actor = User::factory()->create();
    $subject = User::factory()->create();
    $this->actingAs($actor);

    $entry = app(AuditLogger::class)->record('updated', $subject, ['name' => 'Old'], ['name' => 'New']);

    expect($entry->user_id)->toBe($actor->id)
        ->and($entry->auditable_type)->toBe('User')
        ->and($entry->auditable_id)->toBe($subject->id)
        ->and($entry->old_values)->toBe(['name' => 'Old'])
        ->and($entry->new_values)->toBe(['name' => 'New'])
        ->and($entry->ip_address)->toBe('127.0.0.1');
});

it('strips passwords and two-factor secrets from the values', function () {
    $entry = app(AuditLogger::class)->record('updated', null, null, [
        'name' => 'Kept',
        'password' => 'hash',
        'remember_token' => 'token',
        'two_factor_secret' => 'secret',
        'two_factor_recovery_codes' => 'codes',
        'updated_at' => '2026-10-01 10:00:00',
    ]);

    expect($entry->new_values)->toBe(['name' => 'Kept']);
});

it('skips an update that only touched login bookkeeping or secrets', function () {
    $user = User::factory()->create();
    $before = AuditLog::count();

    $user->forceFill(['last_login_at' => now(), 'last_login_ip' => '10.0.0.1', 'two_factor_secret' => encrypt('x')])->save();

    expect(AuditLog::count())->toBe($before);
});
