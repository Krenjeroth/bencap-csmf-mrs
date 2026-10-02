<?php

use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Feature tests run against the real MySQL test database (see phpunit.xml),
| refreshed per test, so CHECK constraints and foreign keys behave exactly
| as they do in development. Reference data is seeded once (TestCase::$seed).
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

pest()->extend(TestCase::class)
    ->in('Unit');

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

/** A System Administrator with two-factor on, ready for admin routes. */
function systemAdministrator(array $attributes = []): User
{
    return User::factory()->systemAdministrator()->create($attributes);
}

/** A user holding exactly the given permissions through a dedicated role. */
function userWithPermissions(array $titles, array $attributes = []): User
{
    $role = Role::factory()->create();
    $role->permissions()->sync(Permission::whereIn('title', $titles)->pluck('id'));

    $user = User::factory()->create($attributes);
    $user->roles()->attach($role);

    return $user;
}

/**
 * Number of audit rows with this event, optionally for one subject id and
 * type (numeric ids repeat across tables, so pass the type for those).
 */
function auditCount(string $event, ?string $subjectId = null, ?string $subjectType = null): int
{
    return AuditLog::query()
        ->where('event', $event)
        ->when($subjectId, fn ($q) => $q->where('auditable_id', $subjectId))
        ->when($subjectType, fn ($q) => $q->where('auditable_type', $subjectType))
        ->count();
}
