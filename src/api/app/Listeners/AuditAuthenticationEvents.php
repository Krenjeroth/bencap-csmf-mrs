<?php

namespace App\Listeners;

use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Http\Request;
use Laravel\Fortify\Events\PasswordUpdatedViaController;
use Laravel\Fortify\Events\RecoveryCodesGenerated;
use Laravel\Fortify\Events\TwoFactorAuthenticationConfirmed;
use Laravel\Fortify\Events\TwoFactorAuthenticationDisabled;
use Laravel\Fortify\Events\TwoFactorAuthenticationFailed;

/**
 * Turns authentication and account-security events into audit entries,
 * and keeps users.last_login_at / last_login_ip current.
 *
 * Registered by Laravel event discovery (each handle* method is bound to
 * the event it type-hints), so it must not also be subscribed manually.
 */
class AuditAuthenticationEvents
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly Request $request,
    ) {}

    public function handleLogin(Login $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        $event->user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $this->request->ip(),
        ])->saveQuietly();

        $this->audit->record('login', $event->user, userId: $event->user->getKey());
    }

    public function handleLogout(Logout $event): void
    {
        if ($event->user instanceof User) {
            $this->audit->record('logout', $event->user, userId: $event->user->getKey());
        }
    }

    public function handleFailed(Failed $event): void
    {
        $user = $event->user instanceof User ? $event->user : null;

        // Record the attempted email only; never the password.
        $this->audit->record(
            'login_failed',
            $user,
            new: ['email' => (string) ($event->credentials['email'] ?? '')],
            userId: $user?->getKey(),
        );
    }

    public function handlePasswordUpdated(PasswordUpdatedViaController $event): void
    {
        $this->audit->record('password_changed', $event->user);
    }

    public function handleTwoFactorConfirmed(TwoFactorAuthenticationConfirmed $event): void
    {
        $this->audit->record('two_factor_enabled', $event->user);
    }

    public function handleTwoFactorDisabled(TwoFactorAuthenticationDisabled $event): void
    {
        $this->audit->record('two_factor_disabled', $event->user);
    }

    public function handleTwoFactorFailed(TwoFactorAuthenticationFailed $event): void
    {
        $this->audit->record('two_factor_failed', $event->user, userId: $event->user->getKey());
    }

    public function handleRecoveryCodes(RecoveryCodesGenerated $event): void
    {
        $this->audit->record('recovery_codes_regenerated', $event->user);
    }
}
