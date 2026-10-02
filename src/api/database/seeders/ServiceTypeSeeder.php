<?php

namespace Database\Seeders;

use App\Models\ServiceType;
use Illuminate\Database\Seeder;

/** The two service types the specification lists. Safe to re-run. */
class ServiceTypeSeeder extends Seeder
{
    public function run(): void
    {
        ServiceType::firstOrCreate(['type' => ServiceType::INTERNAL], [
            'description' => 'Provided to the Provincial Government’s own offices and employees.',
        ]);
        ServiceType::firstOrCreate(['type' => ServiceType::EXTERNAL], [
            'description' => 'Provided to citizens, businesses and other government agencies.',
        ]);
    }
}
