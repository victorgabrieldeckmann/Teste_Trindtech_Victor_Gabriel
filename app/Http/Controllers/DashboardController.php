<?php

namespace App\Http\Controllers;

use App\Http\Requests\DashboardFilterRequest;
use App\Models\Indicator;
use App\Models\IndicatorValue;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(DashboardFilterRequest $request): View
    {
        $filters = $request->validated();
        $indicators = Indicator::query()
            ->withCount('values')
            ->orderBy('name')
            ->get();

        $selectedIndicator = $this->selectIndicator($indicators, $filters['indicator_id'] ?? null);
        $startDate = $filters['start_date'] ?? config('sgs.start_date');
        $endDate = $filters['end_date'] ?? config('sgs.end_date');
        $points = $selectedIndicator?->values()
            ->whereBetween('reference_date', [$startDate, $endDate])
            ->orderBy('reference_date')
            ->get() ?? collect();

        return view('dashboard', [
            'indicators' => $indicators,
            'selectedIndicator' => $selectedIndicator,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'points' => $points,
            'summary' => $this->summarize($points),
        ]);
    }

    /** @param EloquentCollection<int, Indicator> $indicators */
    private function selectIndicator(EloquentCollection $indicators, mixed $requestedId): ?Indicator
    {
        if ($requestedId !== null) {
            return $indicators->firstWhere('id', (int) $requestedId);
        }

        return $indicators->firstWhere('sgs_code', 433) ?? $indicators->first();
    }

    /**
     * @param  Collection<int, IndicatorValue>  $points
     * @return array{accumulated: float, maximum: IndicatorValue, minimum: IndicatorValue}|null
     */
    private function summarize(Collection $points): ?array
    {
        if ($points->isEmpty()) {
            return null;
        }

        $factor = $points->reduce(
            fn (float $result, IndicatorValue $point): float => $result * (1 + ((float) $point->value / 100)),
            1.0,
        );

        return [
            'accumulated' => ($factor - 1) * 100,
            'maximum' => $points->sortByDesc(fn (IndicatorValue $point): float => (float) $point->value)->first(),
            'minimum' => $points->sortBy(fn (IndicatorValue $point): float => (float) $point->value)->first(),
        ];
    }
}
