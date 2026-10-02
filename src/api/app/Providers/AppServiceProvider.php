<?php

namespace App\Providers;

use App\Models\Office;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Service;
use App\Models\ServiceType;
use App\Models\User;
use App\Observers\AuditObserver;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->configureAuthorization();
        $this->configurePasswords();

        User::observe(AuditObserver::class);
        Role::observe(AuditObserver::class);
        Permission::observe(AuditObserver::class);
        Office::observe(AuditObserver::class);
        ServiceType::observe(AuditObserver::class);
        Service::observe(AuditObserver::class);
    }

    /**
     * Permission titles are Gate abilities (ADR 0004): `can:users.view`
     * passes when one of the user's roles has that permission. System
     * Administrator passes every check. Returning null lets Policies decide
     * abilities that are not permission titles (for example "delete-self").
     */
    private function configureAuthorization(): void
    {
        Gate::before(function (User $user, string $ability): ?bool {
            if (! $user->is_active) {
                return false;
            }

            if ($user->isSystemAdministrator()) {
                return true;
            }

            return $user->permissionTitles()->contains($ability) ? true : null;
        });
    }

    private function configurePasswords(): void
    {
        Password::defaults(fn () => Password::min(12)
            ->letters()
            ->mixedCase()
            ->numbers()
            ->symbols());
    }
}
