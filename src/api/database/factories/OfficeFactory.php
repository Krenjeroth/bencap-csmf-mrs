<?php

namespace Database\Factories;

use App\Models\Office;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Office>
 */
class OfficeFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $n = fake()->unique()->numerify('####');

        return [
            'code' => "TST-{$n}",
            'slug' => "tst-{$n}",
            'name' => "Test Office {$n}",
            'is_active' => true,
            'sort_order' => 900,
        ];
    }
}
