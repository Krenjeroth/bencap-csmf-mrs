<?php

namespace Database\Factories;

use App\Models\Office;
use App\Models\Service;
use App\Models\ServiceType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'office_id' => Office::factory(),
            'service_type_id' => fn () => ServiceType::where('type', ServiceType::EXTERNAL)->value('id') ?? ServiceType::factory(),
            'name' => 'Test Service '.fake()->unique()->numerify('#####'),
            'charter_year' => 2026,
            'is_active' => true,
            'sort_order' => 1,
        ];
    }
}
