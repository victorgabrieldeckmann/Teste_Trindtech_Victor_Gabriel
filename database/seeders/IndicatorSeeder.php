<?php

namespace Database\Seeders;

use App\Models\Indicator;
use Illuminate\Database\Seeder;

class IndicatorSeeder extends Seeder
{
    public function run(): void
    {
        Indicator::query()->firstOrCreate(
            ['sgs_code' => 433],
            ['name' => 'IPCA'],
        );

        Indicator::query()->firstOrCreate(
            ['sgs_code' => 189],
            ['name' => 'IGP-M'],
        );
    }
}
