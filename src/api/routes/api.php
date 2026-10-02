<?php

use App\Http\Controllers\Api\V1\Admin\OfficeController;
use App\Http\Controllers\Api\V1\Admin\OptionsController;
use App\Http\Controllers\Api\V1\Admin\PermissionController;
use App\Http\Controllers\Api\V1\Admin\RoleController;
use App\Http\Controllers\Api\V1\Admin\ServiceController;
use App\Http\Controllers\Api\V1\Admin\ServiceTypeController;
use App\Http\Controllers\Api\V1\Admin\UserController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\MeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1
|--------------------------------------------------------------------------
|
| Every route is versioned under /api/v1. A breaking contract change goes
| into /api/v2 alongside v1, never into v1 in place.
|
| Sign-in routes come from Laravel Fortify under /api (config/fortify.php):
| POST /api/login, /api/logout, /api/two-factor-challenge, PUT /api/user/password,
| and the /api/user/two-factor-* routes.
|
*/

Route::prefix('v1')->group(function () {
    Route::get('health', HealthController::class)->name('api.v1.health');

    Route::middleware('auth:sanctum')->group(function () {
        // Reachable before the password change / two-factor steps, so the
        // web app can tell the user which step is next.
        Route::get('me', MeController::class)->name('api.v1.me');

        Route::prefix('admin')
            ->middleware(['password.changed', 'two-factor.enforced'])
            ->name('api.v1.admin.')
            ->group(function () {
                Route::get('role-options', [OptionsController::class, 'roles'])->middleware('can:users.view')->name('options.roles');
                Route::get('permission-options', [OptionsController::class, 'permissions'])->middleware('can:roles.view')->name('options.permissions');
                // Any of users.view, offices.view or services.view (checked in the controller).
                Route::get('office-options', [OptionsController::class, 'offices'])->name('options.offices');
                Route::get('service-type-options', [OptionsController::class, 'serviceTypes'])->middleware('can:services.view')->name('options.service-types');

                Route::get('users', [UserController::class, 'index'])->middleware('can:users.view')->name('users.index');
                Route::post('users', [UserController::class, 'store'])->middleware('can:users.create')->name('users.store');
                Route::get('users/{user}', [UserController::class, 'show'])->middleware('can:users.view')->name('users.show');
                Route::put('users/{user}', [UserController::class, 'update'])->middleware('can:users.update')->name('users.update');
                Route::delete('users/{user}', [UserController::class, 'destroy'])->middleware('can:users.delete')->name('users.destroy');
                Route::put('users/{user}/roles', [UserController::class, 'syncRoles'])->middleware('can:users.update')->name('users.roles');
                Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword'])->middleware('can:users.update')->name('users.reset-password');

                Route::get('roles', [RoleController::class, 'index'])->middleware('can:roles.view')->name('roles.index');
                Route::post('roles', [RoleController::class, 'store'])->middleware('can:roles.create')->name('roles.store');
                Route::get('roles/{role}', [RoleController::class, 'show'])->middleware('can:roles.view')->name('roles.show');
                Route::put('roles/{role}', [RoleController::class, 'update'])->middleware('can:roles.update')->name('roles.update');
                Route::delete('roles/{role}', [RoleController::class, 'destroy'])->middleware('can:roles.delete')->name('roles.destroy');
                Route::put('roles/{role}/permissions', [RoleController::class, 'syncPermissions'])->middleware('can:roles.update')->name('roles.permissions');

                Route::get('permissions', [PermissionController::class, 'index'])->middleware('can:permissions.view')->name('permissions.index');
                Route::post('permissions', [PermissionController::class, 'store'])->middleware('can:permissions.create')->name('permissions.store');
                Route::get('permissions/{permission}', [PermissionController::class, 'show'])->middleware('can:permissions.view')->name('permissions.show');
                Route::put('permissions/{permission}', [PermissionController::class, 'update'])->middleware('can:permissions.update')->name('permissions.update');
                Route::delete('permissions/{permission}', [PermissionController::class, 'destroy'])->middleware('can:permissions.delete')->name('permissions.destroy');

                Route::get('offices', [OfficeController::class, 'index'])->middleware('can:offices.view')->name('offices.index');
                Route::post('offices', [OfficeController::class, 'store'])->middleware('can:offices.create')->name('offices.store');
                Route::get('offices/{office}', [OfficeController::class, 'show'])->middleware('can:offices.view')->name('offices.show');
                Route::put('offices/{office}', [OfficeController::class, 'update'])->middleware('can:offices.update')->name('offices.update');
                Route::delete('offices/{office}', [OfficeController::class, 'destroy'])->middleware('can:offices.delete')->name('offices.destroy');

                Route::get('service-types', [ServiceTypeController::class, 'index'])->middleware('can:service-types.view')->name('service-types.index');
                Route::post('service-types', [ServiceTypeController::class, 'store'])->middleware('can:service-types.create')->name('service-types.store');
                Route::get('service-types/{service_type}', [ServiceTypeController::class, 'show'])->middleware('can:service-types.view')->name('service-types.show');
                Route::put('service-types/{service_type}', [ServiceTypeController::class, 'update'])->middleware('can:service-types.update')->name('service-types.update');
                Route::delete('service-types/{service_type}', [ServiceTypeController::class, 'destroy'])->middleware('can:service-types.delete')->name('service-types.destroy');

                Route::get('services', [ServiceController::class, 'index'])->middleware('can:services.view')->name('services.index');
                Route::post('services', [ServiceController::class, 'store'])->middleware('can:services.create')->name('services.store');
                Route::get('services/{service}', [ServiceController::class, 'show'])->middleware('can:services.view')->name('services.show');
                Route::put('services/{service}', [ServiceController::class, 'update'])->middleware('can:services.update')->name('services.update');
                Route::delete('services/{service}', [ServiceController::class, 'destroy'])->middleware('can:services.delete')->name('services.destroy');
            });
    });
});
