<?php

namespace App\Http\Controllers;

use App\Exceptions\SgsSynchronizationException;
use App\Models\Indicator;
use App\Services\Sgs\SgsSynchronizationService;
use Illuminate\Http\RedirectResponse;

class IndicatorSynchronizationController extends Controller
{
    public function store(
        Indicator $indicator,
        SgsSynchronizationService $synchronizer,
    ): RedirectResponse {
        try {
            $points = $synchronizer->synchronize($indicator);
        } catch (SgsSynchronizationException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        $pointsMessage = $points === 1
            ? '1 ponto processado'
            : "{$points} pontos processados";

        return back()->with('success', "{$indicator->name} sincronizado: {$pointsMessage}.");
    }
}
