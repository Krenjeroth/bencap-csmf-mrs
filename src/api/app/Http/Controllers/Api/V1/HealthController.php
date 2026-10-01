<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Reports whether the API and its database are reachable.
 *
 * Used by the web app's status check and by uptime monitoring. Unlike the
 * framework's /up route, this also proves the database connection works.
 * It deliberately reveals nothing about versions or configuration.
 */
class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $databaseUp = $this->databaseIsReachable();

        return response()->json([
            'status' => $databaseUp ? 'ok' : 'degraded',
            'app' => config('app.name'),
            'database' => $databaseUp ? 'ok' : 'unreachable',
            'time' => now()->toIso8601String(),
        ], $databaseUp ? 200 : 503);
    }

    private function databaseIsReachable(): bool
    {
        try {
            DB::select('select 1');

            return true;
        } catch (Throwable $e) {
            Log::warning('Health check: database unreachable', ['error' => $e->getMessage()]);

            return false;
        }
    }
}
