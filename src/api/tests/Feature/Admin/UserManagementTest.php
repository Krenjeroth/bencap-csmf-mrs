<?php

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use App\Services\AccountGuard;
use Database\Factories\UserFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->actor = systemAdministrator(['name' => 'Acting Sysadmin']);
    $this->actingAs($this->actor);
});

function roleId(string $title): int
{
    return Role::where('title', $title)->value('id');
}

describe('listing', function () {
    it('pages users with their roles', function () {
        User::factory()->count(20)->create();

        $this->getJson('/api/v1/admin/users?per_page=5')
            ->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.total', 21)
            ->assertJsonStructure(['data' => [['id', 'name', 'email', 'is_active', 'roles']], 'links', 'meta']);
    });

    it('searches by name or email', function () {
        User::factory()->create(['name' => 'Maria Dacanay', 'email' => 'm.dacanay@benguet.gov.ph']);
        User::factory()->create(['name' => 'Juan Bayanan', 'email' => 'jbayanan@benguet.gov.ph']);

        $this->getJson('/api/v1/admin/users?q=dacanay')->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Maria Dacanay');
        $this->getJson('/api/v1/admin/users?q=jbayanan@')->assertJsonCount(1, 'data');
    });

    it('treats LIKE wildcards in the search as plain text', function () {
        User::factory()->create(['name' => 'Plain Name']);

        $this->getJson('/api/v1/admin/users?q=%25')->assertOk()->assertJsonCount(0, 'data');
    });

    it('filters by role and status', function () {
        User::factory()->admin()->create();
        User::factory()->inactive()->create();

        $this->getJson('/api/v1/admin/users?role_id='.roleId('Admin'))->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/admin/users?status=inactive')->assertJsonCount(1, 'data');
    });

    it('sorts only by allowed columns', function () {
        User::factory()->create(['name' => 'Aaron Abad']);

        $this->getJson('/api/v1/admin/users?sort=-name')->assertOk()->assertJsonPath('data.0.name', 'Acting Sysadmin');
        $this->getJson('/api/v1/admin/users?sort=password')->assertUnprocessable()->assertJsonValidationErrors('sort');
    });

    it('limits page size to 1–100', function (int $size, int $status) {
        $this->getJson("/api/v1/admin/users?per_page={$size}")->assertStatus($status);
    })->with([[1, 200], [100, 200], [0, 422], [101, 422]]);

    it('hides deleted users', function () {
        $gone = User::factory()->create();
        $gone->delete();

        $this->getJson('/api/v1/admin/users')->assertJsonMissing(['id' => $gone->id]);
        $this->getJson("/api/v1/admin/users/{$gone->id}")->assertNotFound();
    });
});

describe('creating', function () {
    it('creates an account with a one-time password that must be changed', function () {
        $response = $this->postJson('/api/v1/admin/users', [
            'name' => 'Office Clerk',
            'email' => 'Clerk@Benguet.gov.ph',
            'role_ids' => [roleId('Admin')],
        ])->assertCreated()
            ->assertJsonPath('data.email', 'clerk@benguet.gov.ph')
            ->assertJsonPath('data.must_change_password', true)
            ->assertJsonPath('data.roles.0.title', 'Admin');

        $password = $response->json('temporary_password');
        $user = User::where('email', 'clerk@benguet.gov.ph')->firstOrFail();

        expect(Hash::check($password, $user->password))->toBeTrue()
            ->and(Validator::make(['p' => $password], ['p' => Password::default()])->passes())->toBeTrue()
            ->and(auditCount('created', $user->id))->toBe(1)
            ->and(auditCount('roles_synced', $user->id))->toBe(1);
    });

    it('never writes the password to the audit log', function () {
        $password = $this->postJson('/api/v1/admin/users', ['name' => 'A', 'email' => 'a@benguet.gov.ph', 'role_ids' => []])
            ->json('temporary_password');

        expect(DB::table('audit_logs')->get()->toJson())->not->toContain($password)
            ->not->toContain('"password"');
    });

    it('validates the input', function (array $payload, string $field) {
        User::factory()->create(['email' => 'taken@benguet.gov.ph']);

        $this->postJson('/api/v1/admin/users', [...['name' => 'Valid', 'email' => 'new@benguet.gov.ph', 'role_ids' => []], ...$payload])
            ->assertUnprocessable()
            ->assertJsonValidationErrors($field);
    })->with([
        'name missing' => [['name' => ''], 'name'],
        'name 151 chars' => [['name' => str_repeat('a', 151)], 'name'],
        'email invalid' => [['email' => 'not-an-email'], 'email'],
        'email taken' => [['email' => 'TAKEN@benguet.gov.ph'], 'email'],
        'roles missing' => [['role_ids' => null], 'role_ids'],
        'unknown role' => [['role_ids' => [99999]], 'role_ids.0'],
    ]);

    it('accepts a 150-character name', function () {
        $this->postJson('/api/v1/admin/users', ['name' => str_repeat('a', 150), 'email' => 'long@benguet.gov.ph', 'role_ids' => []])
            ->assertCreated();
    });

    it('does not reuse the email of a deleted account', function () {
        User::factory()->create(['email' => 'former@benguet.gov.ph'])->delete();

        $this->postJson('/api/v1/admin/users', ['name' => 'New', 'email' => 'former@benguet.gov.ph', 'role_ids' => []])
            ->assertJsonValidationErrors('email');
    });

    it('lets only a System Administrator create another System Administrator', function () {
        $this->actingAs(userWithPermissions(['users.create']));

        $this->postJson('/api/v1/admin/users', ['name' => 'X', 'email' => 'x@benguet.gov.ph', 'role_ids' => [roleId('System Administrator')]])
            ->assertJsonValidationErrors('role_ids');
    });
});

describe('updating', function () {
    it('edits name, email and status', function () {
        $user = User::factory()->create();

        $this->putJson("/api/v1/admin/users/{$user->id}", ['name' => 'Renamed', 'email' => 'NEW@benguet.gov.ph'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Renamed')
            ->assertJsonPath('data.email', 'new@benguet.gov.ph');

        expect(auditCount('updated', $user->id))->toBe(1);
    });

    it('keeps the same email without a uniqueness error', function () {
        $user = User::factory()->create(['email' => 'same@benguet.gov.ph']);

        $this->putJson("/api/v1/admin/users/{$user->id}", ['email' => 'same@benguet.gov.ph'])->assertOk();
    });

    it('signs a deactivated user out everywhere', function () {
        $user = User::factory()->create();
        DB::table('sessions')->insert(['id' => 'sess-1', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()]);
        $user->createToken('device');

        $this->putJson("/api/v1/admin/users/{$user->id}", ['is_active' => false])->assertOk();

        expect(DB::table('sessions')->where('user_id', $user->id)->count())->toBe(0)
            ->and($user->tokens()->count())->toBe(0);
    });

    it('will not let you deactivate yourself', function () {
        $this->putJson("/api/v1/admin/users/{$this->actor->id}", ['is_active' => false])
            ->assertJsonValidationErrors('is_active');
    });

    it('will not deactivate the last active System Administrator', function () {
        $this->actingAs(userWithPermissions(['users.update']));

        $this->putJson("/api/v1/admin/users/{$this->actor->id}", ['is_active' => false])
            ->assertJsonValidationErrors('is_active');
    });

    it('deactivates a System Administrator when another remains', function () {
        $other = systemAdministrator();

        $this->putJson("/api/v1/admin/users/{$other->id}", ['is_active' => false])->assertOk();
    });
});

describe('roles', function () {
    it('replaces a user\'s roles and records the change', function () {
        $user = User::factory()->admin()->create();
        $custom = Role::factory()->create(['title' => 'Encoder']);

        $this->putJson("/api/v1/admin/users/{$user->id}/roles", ['role_ids' => [$custom->id]])
            ->assertOk()
            ->assertJsonPath('data.roles.0.title', 'Encoder');

        $entry = AuditLog::where('event', 'roles_synced')->where('auditable_id', $user->id)->firstOrFail();
        expect($entry->old_values)->toBe(['roles' => ['Admin']])
            ->and($entry->new_values)->toBe(['roles' => ['Encoder']]);
    });

    it('will not let you change your own roles', function () {
        $this->putJson("/api/v1/admin/users/{$this->actor->id}/roles", ['role_ids' => []])
            ->assertJsonValidationErrors('role_ids');
    });

    it('stops non-System Administrators from granting the System Administrator role', function () {
        $this->actingAs(userWithPermissions(['users.update']));
        $user = User::factory()->create();

        $this->putJson("/api/v1/admin/users/{$user->id}/roles", ['role_ids' => [roleId('System Administrator')]])
            ->assertJsonValidationErrors('role_ids');

        expect($user->fresh()->isSystemAdministrator())->toBeFalse();
    });

    // Through the API this case is already blocked by the escalation rule
    // (only a System Administrator may remove the role, which means two
    // exist). The guard still refuses it directly, as defence in depth.
    it('refuses to remove the System Administrator role from the last one', function () {
        $guard = app(AccountGuard::class);

        expect(fn () => $guard->assertMayChangeSystemRole($this->actor, $this->actor->load('roles'), collect()))
            ->toThrow(ValidationException::class, 'last active System Administrator');
    });

    it('removes the System Administrator role when another one remains', function () {
        $other = systemAdministrator();

        $this->putJson("/api/v1/admin/users/{$other->id}/roles", ['role_ids' => [roleId('Admin')]])->assertOk();
        expect($other->fresh()->isSystemAdministrator())->toBeFalse();
    });
});

describe('resetting a password', function () {
    it('issues a one-time password and signs the user out', function () {
        $user = User::factory()->create();
        DB::table('sessions')->insert(['id' => 'sess-2', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()]);

        $password = $this->postJson("/api/v1/admin/users/{$user->id}/reset-password")
            ->assertOk()
            ->json('temporary_password');

        $user->refresh();
        expect(Hash::check($password, $user->password))->toBeTrue()
            ->and(Hash::check(UserFactory::PASSWORD, $user->password))->toBeFalse()
            ->and($user->must_change_password)->toBeTrue()
            ->and(DB::table('sessions')->where('user_id', $user->id)->count())->toBe(0)
            ->and(auditCount('password_reset', $user->id))->toBe(1);
    });

    it('keeps two-factor unless asked to reset it', function () {
        $user = User::factory()->withTwoFactor()->create();

        $this->postJson("/api/v1/admin/users/{$user->id}/reset-password")->assertOk();
        expect($user->fresh()->hasEnabledTwoFactor())->toBeTrue();

        $this->postJson("/api/v1/admin/users/{$user->id}/reset-password", ['reset_two_factor' => true])->assertOk();
        expect($user->fresh()->hasEnabledTwoFactor())->toBeFalse();
    });
});

describe('deleting', function () {
    it('soft-deletes and signs the user out', function () {
        $user = User::factory()->create();

        $this->deleteJson("/api/v1/admin/users/{$user->id}")->assertNoContent();

        $this->assertSoftDeleted($user);
        expect(auditCount('deleted', $user->id))->toBe(1);
    });

    it('will not let you delete yourself', function () {
        $this->deleteJson("/api/v1/admin/users/{$this->actor->id}")->assertJsonValidationErrors('user');
    });

    it('will not delete the last active System Administrator', function () {
        $this->actingAs(userWithPermissions(['users.delete']));

        $this->deleteJson("/api/v1/admin/users/{$this->actor->id}")->assertJsonValidationErrors('user');
        $this->assertNotSoftDeleted($this->actor);
    });

    it('returns 404 for an unknown id', function () {
        $this->deleteJson('/api/v1/admin/users/0199a3f2-0000-7000-8000-000000000000')->assertNotFound();
    });
});
