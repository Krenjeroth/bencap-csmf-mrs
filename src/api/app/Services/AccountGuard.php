<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Business rules that protect against lockout and privilege escalation.
 *
 * These are enforced here instead of in Policies because Gate::before lets
 * System Administrators pass every Policy check, and these rules must hold
 * for System Administrators too.
 */
class AccountGuard
{
    /** Nobody may delete, deactivate or change the roles of their own account. */
    public function assertNotSelf(User $actor, User $target, string $field, string $action): void
    {
        if ($actor->is($target)) {
            throw ValidationException::withMessages([
                $field => "You cannot {$action} your own account.",
            ]);
        }
    }

    /** The system must always keep at least one active System Administrator. */
    public function assertKeepsASystemAdministrator(User $target, string $field): void
    {
        if (! $target->isSystemAdministrator() || ! $target->is_active) {
            return;
        }

        $others = User::query()
            ->whereKeyNot($target->getKey())
            ->where('is_active', true)
            ->whereHas('roles', fn ($q) => $q->where('is_system', true))
            ->exists();

        if (! $others) {
            throw ValidationException::withMessages([
                $field => 'This is the last active System Administrator. Make another account System Administrator first.',
            ]);
        }
    }

    /**
     * Only a System Administrator may grant or remove the System
     * Administrator role, so users.update cannot be used to self-escalate.
     *
     * @param  Collection<int, int>  $newRoleIds
     */
    public function assertMayChangeSystemRole(User $actor, User $target, Collection $newRoleIds): void
    {
        $systemRoleIds = Role::where('is_system', true)->pluck('id');
        $had = $target->roles->pluck('id')->intersect($systemRoleIds)->isNotEmpty();
        $will = $newRoleIds->intersect($systemRoleIds)->isNotEmpty();

        if ($had !== $will && ! $actor->isSystemAdministrator()) {
            throw ValidationException::withMessages([
                'role_ids' => 'Only a System Administrator can grant or remove the System Administrator role.',
            ]);
        }

        if ($had && ! $will) {
            $this->assertKeepsASystemAdministrator($target, 'role_ids');
        }
    }
}
