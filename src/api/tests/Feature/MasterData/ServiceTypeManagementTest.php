<?php

use App\Models\ServiceType;

beforeEach(function () {
    $this->actingAs(systemAdministrator());
});

it('lists Internal and External with their service counts', function () {
    $this->getJson('/api/v1/admin/service-types')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.type', 'External')
        ->assertJsonPath('data.0.services_count', 235)
        ->assertJsonPath('data.1.type', 'Internal')
        ->assertJsonPath('data.1.services_count', 6);
});

it('creates, renames and deletes an unused type', function () {
    $id = $this->postJson('/api/v1/admin/service-types', ['type' => 'Frontline'])->assertCreated()->json('data.id');

    $this->putJson("/api/v1/admin/service-types/{$id}", ['type' => 'Front-line', 'description' => 'Walk-in counters'])
        ->assertOk()
        ->assertJsonPath('data.type', 'Front-line');

    $this->deleteJson("/api/v1/admin/service-types/{$id}")->assertNoContent();
});

it('validates the type name', function (string $type) {
    $this->postJson('/api/v1/admin/service-types', ['type' => $type])->assertJsonValidationErrors('type');
})->with(['', 'External', 'internal']);

it('accepts a 50-character type and rejects 51', function () {
    $this->postJson('/api/v1/admin/service-types', ['type' => str_repeat('t', 50)])->assertCreated();
    $this->postJson('/api/v1/admin/service-types', ['type' => str_repeat('u', 51)])->assertJsonValidationErrors('type');
});

it('will not delete a type that services use', function () {
    $external = ServiceType::where('type', 'External')->firstOrFail();

    $this->deleteJson("/api/v1/admin/service-types/{$external->id}")->assertJsonValidationErrors('service_type');
    $this->assertModelExists($external);
});
