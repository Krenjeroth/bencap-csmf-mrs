<?php

use App\Http\Controllers\Api\V1\HealthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1
|--------------------------------------------------------------------------
|
| Every route is versioned under /api/v1. A breaking contract change goes
| into /api/v2 alongside v1, never into v1 in place.
|
*/

Route::prefix('v1')->group(function () {
    Route::get('health', HealthController::class)->name('api.v1.health');
});
