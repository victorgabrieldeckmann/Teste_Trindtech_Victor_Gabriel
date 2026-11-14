<?php

namespace Database\Seeders;

use App\Models\Indicator;
use App\Services\Sgs\SgsSynchronizationService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OnlineIndicatorValuesSeeder extends Seeder
{
    public function run(SgsSynchronizationService $synchronizer): void
    {
        DB::transaction(function () use ($synchronizer): void {
            Indicator::query()
                ->whereIn('sgs_code', [433, 189])
                ->orderBy('id')
                ->each(fn (Indicator $indicator) => $synchronizer->synchronize($indicator));
        });
    }
}
