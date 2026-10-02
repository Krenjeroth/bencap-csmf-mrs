<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use App\Support\AuditLogger;
use App\Support\TemporaryPassword;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Account use cases for the Users admin screen. Each runs in a transaction
 * and leaves exactly one audit entry per change.
 */
class UserAccountService
{
    public function __construct(
        private readonly AccountGuard $guard,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * Creates an account with a one-time password that must be changed on
     * first sign-in.
     *
     * @param  array{name: string, email: string, is_active?: bool, office_id?: int|null}  $data
     * @param  Collection<int, int>  $roleIds
     * @return array{0: User, 1: string} The user and the temporary password (shown once).
     */
    public function create(User $actor, array $data, Collection $roleIds): array
    {
        $this->guard->assertMayChangeSystemRole($actor, new User, $roleIds);
        $this->guard->assertMayAssignOffice($actor, $data['office_id'] ?? null);

        return DB::transaction(function () use ($data, $roleIds) {
            $password = TemporaryPassword::generate();

            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $password,
                'must_change_password' => true,
                'is_active' => $data['is_active'] ?? true,
                'office_id' => $data['office_id'] ?? null,
            ]);
            $user->forceFill(['email_verified_at' => now()])->saveQuietly();

            if ($roleIds->isNotEmpty()) {
                $user->roles()->sync($roleIds);
                $this->audit->record('roles_synced', $user, ['roles' => []], ['roles' => $this->titles($roleIds)]);
            }

            return [$user->load(['roles', 'office']), $password];
        });
    }

    /** @param  array{name?: string, email?: string, is_active?: bool, office_id?: int|null}  $data */
    public function update(User $actor, User $user, array $data): User
    {
        if (array_key_exists('office_id', $data)) {
            $this->guard->assertMayAssignOffice($actor, $data['office_id']);
        }

        if (array_key_exists('is_active', $data) && ! $data['is_active'] && $user->is_active) {
            $this->guard->assertNotSelf($actor, $user, 'is_active', 'deactivate');
            $this->guard->assertKeepsASystemAdministrator($user, 'is_active');
        }

        return DB::transaction(function () use ($user, $data) {
            $user->fill($data)->save();

            if (! $user->is_active) {
                $this->endSessions($user);
            }

            return $user->load(['roles', 'office']);
        });
    }

    /** @param  Collection<int, int>  $roleIds */
    public function syncRoles(User $actor, User $user, Collection $roleIds): User
    {
        $this->guard->assertNotSelf($actor, $user, 'role_ids', 'change the roles of');
        $user->loadMissing('roles');
        $this->guard->assertMayChangeSystemRole($actor, $user, $roleIds);

        return DB::transaction(function () use ($user, $roleIds) {
            $before = $user->roles->pluck('title')->sort()->values()->all();
            $user->roles()->sync($roleIds);
            $user->forgetPermissionCache();
            $after = $this->titles($roleIds);

            if ($before !== $after) {
                $this->audit->record('roles_synced', $user, ['roles' => $before], ['roles' => $after]);
            }

            return $user->load(['roles', 'office']);
        });
    }

    /**
     * Sets a new one-time password, signs the user out everywhere and,
     * optionally, turns off two-factor login (lost authenticator).
     *
     * @return string The temporary password (shown once).
     */
    public function resetPassword(User $user, bool $resetTwoFactor): string
    {
        return DB::transaction(function () use ($user, $resetTwoFactor) {
            $password = TemporaryPassword::generate();
            $attributes = ['password' => $password, 'must_change_password' => true];

            if ($resetTwoFactor) {
                $attributes += [
                    'two_factor_secret' => null,
                    'two_factor_recovery_codes' => null,
                    'two_factor_confirmed_at' => null,
                ];
            }

            $user->forceFill($attributes)->saveQuietly();
            $this->endSessions($user);
            $this->audit->record('password_reset', $user, new: ['two_factor_reset' => $resetTwoFactor]);

            return $password;
        });
    }

    public function delete(User $actor, User $user): void
    {
        $this->guard->assertNotSelf($actor, $user, 'user', 'delete');
        $this->guard->assertKeepsASystemAdministrator($user, 'user');

        DB::transaction(function () use ($user) {
            $this->endSessions($user);
            $user->delete();
        });
    }

    /** Signs the user out of every browser session and revokes API tokens. */
    private function endSessions(User $user): void
    {
        DB::table('sessions')->where('user_id', $user->getKey())->delete();
        $user->tokens()->delete();
    }

    /**
     * @param  Collection<int, int>  $roleIds
     * @return list<string>
     */
    private function titles(Collection $roleIds): array
    {
        return Role::whereIn('id', $roleIds)->pluck('title')->sort()->values()->all();
    }
}
