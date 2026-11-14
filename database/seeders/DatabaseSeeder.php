<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            IndicatorSeeder::class,
            DemoUserSeeder::class,
        ]);

        $valuesSeeder = match (config('sgs.seed_mode')) {
            'online' => OnlineIndicatorValuesSeeder::class,
            'offline' => OfflineIndicatorValuesSeeder::class,
            'skip' => null,
            default => throw new \InvalidArgumentException('SGS_SEED_MODE deve ser online, offline ou skip.'),
        };

        if ($valuesSeeder !== null) {
            $this->call($valuesSeeder);
        }
    }
}
