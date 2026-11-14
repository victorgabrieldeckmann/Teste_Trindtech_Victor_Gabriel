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
        $imports = Indicator::query()
            ->whereIn('sgs_code', [433, 189])
            ->orderBy('id')
            ->get()
            ->map(fn (Indicator $indicator): array => [
                'indicator' => $indicator,
                'rows' => $synchronizer->validatePayload(
                    $indicator,
                    $synchronizer->fetchPayload($indicator),
                ),
            ]);

        DB::transaction(function () use ($imports, $synchronizer): void {
            $imports->each(fn (array $import): int => $synchronizer->persistRows(
                $import['indicator'],
                $import['rows'],
            ));
        });
    }
}
