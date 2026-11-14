<?php

namespace App\Http\Controllers;

use App\Exceptions\SgsSynchronizationException;
use App\Models\Indicator;
use App\Services\Sgs\SgsSynchronizationService;
use Illuminate\Http\RedirectResponse;

class DefaultIndicatorsController extends Controller
{
    public function store(SgsSynchronizationService $synchronizer): RedirectResponse
    {
        $createdIndicators = collect([
            433 => 'IPCA',
            189 => 'IGP-M',
        ])->map(function (string $name, int $sgsCode): Indicator {
            return Indicator::query()->firstOrCreate(
                ['sgs_code' => $sgsCode],
                ['name' => $name],
            );
        })->filter(fn (Indicator $indicator): bool => $indicator->wasRecentlyCreated);

        if ($createdIndicators->isEmpty()) {
            return redirect()
                ->route('dashboard')
                ->with('success', 'Os indicadores padrão já estão cadastrados.');
        }

        $processedPoints = 0;
        $failedIndicators = [];

        foreach ($createdIndicators as $indicator) {
            try {
                $processedPoints += $synchronizer->synchronize($indicator);
            } catch (SgsSynchronizationException) {
                $failedIndicators[] = $indicator->name;
            }
        }

        $pointsMessage = $processedPoints === 1
            ? '1 ponto processado'
            : "{$processedPoints} pontos processados";

        if ($failedIndicators !== []) {
            return redirect()
                ->route('dashboard')
                ->with(
                    'error',
                    'Os indicadores foram restaurados, mas não foi possível sincronizar agora: '
                    .implode(', ', $failedIndicators).'.',
                );
        }

        return redirect()
            ->route('dashboard')
            ->with(
                'success',
                "Indicadores padrão restaurados: {$pointsMessage}.",
            );
    }
}
