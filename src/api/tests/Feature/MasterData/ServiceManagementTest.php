<?php

use App\Models\Office;
use App\Models\Service;
use App\Models\ServiceType;
use App\Models\User;

function office(string $code): Office
{
    return Office::where('code', $code)->firstOrFail();
}

function typeId(string $type): int
{
    return ServiceType::where('type', $type)->value('id');
}

describe('as System Administrator', function () {
    beforeEach(function () {
        $this->actingAs(systemAdministrator());
    });

    it('lists services in charter order with office and type', function () {
        $this->getJson('/api/v1/admin/services?per_page=3')
            ->assertOk()
            ->assertJsonPath('meta.total', 241)
            ->assertJsonPath('data.0.office.code', 'OG')
            ->assertJsonPath('data.0.name', "Issuance of Governor's Endorsement / Recommendation")
            ->assertJsonPath('data.0.service_type.type', 'External')
            ->assertJsonPath('data.0.charter_year', 2026);
    });

    it('filters by office, type, year, status and name', function () {
        $pho = office('PHO');
        Service::where('office_id', $pho->id)->limit(1)->update(['is_active' => false]);

        $this->getJson("/api/v1/admin/services?office_id={$pho->id}")->assertJsonPath('meta.total', 7);
        $this->getJson("/api/v1/admin/services?office_id={$pho->id}&status=inactive")->assertJsonPath('meta.total', 1);
        $this->getJson('/api/v1/admin/services?service_type_id='.typeId('Internal'))->assertJsonPath('meta.total', 6);
        $this->getJson('/api/v1/admin/services?charter_year=2025')->assertJsonPath('meta.total', 0);
        $this->getJson('/api/v1/admin/services?q=hemodialysis')->assertJsonPath('meta.total', 4);
    });

    it('creates a service at the end of its office list', function () {
        $pho = office('PHO');

        $this->postJson('/api/v1/admin/services', [
            'office_id' => $pho->id,
            'service_type_id' => typeId('Internal'),
            'name' => 'Issuance of Medical Clearance for Employees',
        ])->assertCreated()
            ->assertJsonPath('data.charter_year', now()->year)
            ->assertJsonPath('data.service_type.type', 'Internal');

        expect(Service::where('name', 'Issuance of Medical Clearance for Employees')->value('sort_order'))
            ->toBe((int) Service::where('office_id', $pho->id)->where('charter_year', now()->year)->max('sort_order'));
    });

    it('keeps names unique per office and charter year only', function () {
        $name = 'Microbiological Water Analysis';

        $this->postJson('/api/v1/admin/services', ['office_id' => office('PHO')->id, 'service_type_id' => typeId('External'), 'name' => $name, 'charter_year' => 2026])
            ->assertJsonValidationErrors('name');
        $this->postJson('/api/v1/admin/services', ['office_id' => office('PHO')->id, 'service_type_id' => typeId('External'), 'name' => $name, 'charter_year' => 2027])
            ->assertCreated();
        $this->postJson('/api/v1/admin/services', ['office_id' => office('PTO')->id, 'service_type_id' => typeId('External'), 'name' => $name, 'charter_year' => 2026])
            ->assertCreated();
    });

    it('validates services', function (array $payload, string $field) {
        $this->postJson('/api/v1/admin/services', [...['office_id' => office('PHO')->id, 'service_type_id' => typeId('External'), 'name' => 'Valid'], ...$payload])
            ->assertJsonValidationErrors($field);
    })->with([
        'office missing' => [['office_id' => null], 'office_id'],
        'unknown office' => [['office_id' => 99999], 'office_id'],
        'type missing' => [['service_type_id' => null], 'service_type_id'],
        'name missing' => [['name' => '  '], 'name'],
        'name 256 chars' => [['name' => str_repeat('s', 256)], 'name'],
        'year too early' => [['charter_year' => 1999], 'charter_year'],
        'year too late' => [['charter_year' => 2101], 'charter_year'],
    ]);

    it('accepts a 255-character name', function () {
        $this->postJson('/api/v1/admin/services', ['office_id' => office('PHO')->id, 'service_type_id' => typeId('External'), 'name' => str_repeat('s', 255)])
            ->assertCreated();
    });

    it('marks a service Internal and deactivates it', function () {
        $service = Service::where('name', 'Payment of Approved Vouchers and Payrolls')->firstOrFail();

        $this->putJson("/api/v1/admin/services/{$service->id}", ['service_type_id' => typeId('Internal'), 'is_active' => false])
            ->assertOk()
            ->assertJsonPath('data.service_type.type', 'Internal')
            ->assertJsonPath('data.is_active', false);

        expect(auditCount('updated', (string) $service->id, 'Service'))->toBe(1);
    });

    it('deletes a service no feedback refers to', function () {
        $service = Service::factory()->create();

        $this->deleteJson("/api/v1/admin/services/{$service->id}")->assertNoContent();
        $this->assertModelMissing($service);
    });
});

describe('as an office-limited Admin', function () {
    beforeEach(function () {
        $this->pho = office('PHO');
        $this->admin = userWithPermissions(['services.view', 'services.create', 'services.update', 'services.delete'], ['office_id' => $this->pho->id]);
        $this->actingAs($this->admin);
    });

    it('sees only their own office’s services', function () {
        $this->getJson('/api/v1/admin/services?per_page=100')
            ->assertOk()
            ->assertJsonPath('meta.total', 7)
            ->assertJsonMissing(['code' => 'PTO']);

        // Asking for another office still returns only their own.
        $this->getJson('/api/v1/admin/services?office_id='.office('PTO')->id)->assertJsonPath('meta.total', 0);
    });

    it('cannot open, change or delete another office’s service', function () {
        $other = Service::where('office_id', office('PTO')->id)->firstOrFail();

        $this->getJson("/api/v1/admin/services/{$other->id}")->assertNotFound();
        $this->putJson("/api/v1/admin/services/{$other->id}", ['name' => 'Hijacked'])->assertNotFound();
        $this->deleteJson("/api/v1/admin/services/{$other->id}")->assertNotFound();
    });

    it('cannot add a service to another office or move one there', function () {
        $this->postJson('/api/v1/admin/services', ['office_id' => office('PTO')->id, 'service_type_id' => typeId('External'), 'name' => 'X'])
            ->assertJsonValidationErrors('office_id');

        $own = Service::where('office_id', $this->pho->id)->firstOrFail();
        $this->putJson("/api/v1/admin/services/{$own->id}", ['office_id' => office('PTO')->id])->assertJsonValidationErrors('office_id');
    });

    it('can add a service to their own office', function () {
        $this->postJson('/api/v1/admin/services', ['office_id' => $this->pho->id, 'service_type_id' => typeId('External'), 'name' => 'New PHO Service'])
            ->assertCreated();
    });
});

it('lets a user without an office see every office’s services', function () {
    $this->actingAs(User::factory()->admin()->create(['office_id' => null]));

    $this->getJson('/api/v1/admin/services')->assertJsonPath('meta.total', 241);
});
