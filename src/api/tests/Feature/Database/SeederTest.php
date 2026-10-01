<?php

use App\Models\Permission;
use App\Models\Role;
use App\Support\PermissionCatalog;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\DB;

function snapshot(): array
{
    return [
        'roles' => Role::count(),
        'permissions' => Permission::count(),
        'permission_role' => DB::table('permission_role')->count(),
        'users' => DB::table('users')->count(),
    ];
}

it('seeds the catalog, both roles and no user accounts', function () {
    expect(Permission::pluck('title')->all())->toEqualCanonicalizing(PermissionCatalog::titles())
        ->and(Permission::where('is_protected', false)->count())->toBe(0)
        ->and(Role::pluck('title')->all())->toEqualCanonicalizing(['System Administrator', 'Admin'])
        ->and(DB::table('users')->count())->toBe(0);
});

it('gives System Administrator every permission and Admin its office defaults', function () {
    $system = Role::where('title', 'System Administrator')->first();
    $admin = Role::where('title', 'Admin')->first();

    expect($system->is_system)->toBeTrue()
        ->and($system->permissions()->count())->toBe(Permission::count())
        ->and($admin->is_system)->toBeFalse()
        ->and($admin->permissions()->pluck('title')->all())->toEqualCanonicalizing(PermissionCatalog::ADMIN_DEFAULTS);
});

it('produces the same data when run twice', function () {
    $before = snapshot();

    $this->seed(DatabaseSeeder::class);
    $this->seed(DatabaseSeeder::class);

    expect(snapshot())->toBe($before);
});

it('keeps changes made to the Admin role in the role editor', function () {
    $admin = Role::where('title', 'Admin')->first();
    $admin->permissions()->sync(Permission::where('title', 'dashboard.view')->pluck('id'));

    $this->seed(DatabaseSeeder::class);

    expect($admin->permissions()->pluck('title')->all())->toBe(['dashboard.view']);
});

it('re-grants every permission to System Administrator, including custom ones', function () {
    $system = Role::where('title', 'System Administrator')->first();
    $custom = Permission::factory()->create(['title' => 'reports.print']);
    $system->permissions()->detach(Permission::where('title', 'users.view')->value('id'));

    $this->seed(DatabaseSeeder::class);

    expect($system->permissions()->count())->toBe(Permission::count())
        ->and($system->permissions()->whereKey($custom->id)->exists())->toBeTrue();
});
