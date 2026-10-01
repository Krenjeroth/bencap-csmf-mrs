<?php

use Illuminate\Support\Facades\DB;

it('reports ok when the API and database are reachable', function () {
    $this->getJson('/api/v1/health')
        ->assertOk()
        ->assertExactJsonStructure(['status', 'app', 'database', 'time'])
        ->assertJson([
            'status' => 'ok',
            'app' => config('app.name'),
            'database' => 'ok',
        ]);
});

it('returns 503 and degraded status when the database is unreachable', function () {
    $original = config('database.default');

    // A copy of the real connection pointed at a port nothing listens on,
    // with a 1-second connect timeout so the test stays fast.
    $connection = config("database.connections.{$original}");
    config(['database.connections.unreachable' => array_merge($connection, [
        'host' => '127.0.0.1',
        'port' => 1,
        'options' => ($connection['options'] ?? []) + [PDO::ATTR_TIMEOUT => 1],
    ])]);
    config(['database.default' => 'unreachable']);

    try {
        $this->getJson('/api/v1/health')
            ->assertStatus(503)
            ->assertJson(['status' => 'degraded', 'database' => 'unreachable']);
    } finally {
        // RefreshDatabase rolls back the default connection after the test.
        config(['database.default' => $original]);
        DB::purge('unreachable');
    }
});

it('does not expose framework or version details', function () {
    $body = $this->getJson('/api/v1/health')->json();

    expect($body)->not->toHaveKeys(['version', 'php', 'laravel', 'environment']);
});

it('keeps the framework liveness route available', function () {
    $this->get('/up')->assertOk();
});

it('rejects write methods on the health endpoint', function (string $method) {
    $this->json($method, '/api/v1/health')->assertStatus(405);
})->with(['POST', 'PUT', 'PATCH', 'DELETE']);
