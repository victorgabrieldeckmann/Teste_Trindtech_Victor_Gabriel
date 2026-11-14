<?php

namespace App\Services\Sgs;

use App\Exceptions\SgsSynchronizationException;
use App\Models\Indicator;
use App\Models\IndicatorValue;
use DateTimeImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use JsonException;

class SgsSynchronizationService
{
    public function synchronize(Indicator $indicator): int
    {
        return $this->synchronizeFromPayload($indicator, $this->fetchPayload($indicator));
    }

    /** @return array<mixed> */
    public function fetchPayload(Indicator $indicator): array
    {
        try {
            $response = Http::acceptJson()
                ->timeout(config('sgs.timeout'))
                ->get($this->endpointFor($indicator), [
                    'formato' => 'json',
                    'dataInicial' => $this->formatQueryDate(config('sgs.start_date')),
                    'dataFinal' => $this->formatQueryDate(config('sgs.end_date')),
                ])
                ->throw();

            $payload = json_decode($response->body(), true, flags: JSON_THROW_ON_ERROR);
        } catch (ConnectionException|RequestException|JsonException $exception) {
            throw new SgsSynchronizationException(
                "Não foi possível obter a série SGS {$indicator->sgs_code}.",
                previous: $exception,
            );
        }

        if (! is_array($payload)) {
            throw new SgsSynchronizationException(
                "A série SGS {$indicator->sgs_code} retornou uma resposta inválida.",
            );
        }

        return $payload;
    }

    /**
     * @param  array<mixed>  $payload
     */
    public function synchronizeFromPayload(Indicator $indicator, array $payload): int
    {
        return $this->persistRows($indicator, $this->validatePayload($indicator, $payload));
    }

    /**
     * @param  array<mixed>  $payload
     * @return list<array{indicator_id: int, reference_date: string, value: string, created_at: mixed, updated_at: mixed}>
     */
    public function validatePayload(Indicator $indicator, array $payload): array
    {
        if ($payload === [] || ! array_is_list($payload)) {
            throw new SgsSynchronizationException(
                "A série SGS {$indicator->sgs_code} não contém pontos válidos.",
            );
        }

        $now = now();
        $rows = [];
        $seenDates = [];

        foreach ($payload as $point) {
            if (! is_array($point)) {
                throw $this->invalidPoint($indicator);
            }

            $referenceDate = $this->parseDate($point['data'] ?? null, $indicator);
            $value = $this->parseValue($point['valor'] ?? null, $indicator);

            if (isset($seenDates[$referenceDate])) {
                throw new SgsSynchronizationException(
                    "A série SGS {$indicator->sgs_code} repetiu a data {$referenceDate}.",
                );
            }

            $seenDates[$referenceDate] = true;
            $rows[] = [
                'indicator_id' => $indicator->id,
                'reference_date' => $referenceDate,
                'value' => $value,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        return $rows;
    }

    /**
     * @param  list<array{indicator_id: int, reference_date: string, value: string, created_at: mixed, updated_at: mixed}>  $rows
     */
    public function persistRows(Indicator $indicator, array $rows): int
    {
        try {
            DB::transaction(function () use ($rows): void {
                IndicatorValue::query()->upsert(
                    $rows,
                    ['indicator_id', 'reference_date'],
                    ['value', 'updated_at'],
                );
            });
        } catch (QueryException $exception) {
            throw new SgsSynchronizationException(
                "Não foi possível salvar a série SGS {$indicator->sgs_code}.",
                previous: $exception,
            );
        }

        return count($rows);
    }

    private function endpointFor(Indicator $indicator): string
    {
        return sprintf(
            '%s.%d/dados',
            rtrim(config('sgs.base_url'), '/'),
            $indicator->sgs_code,
        );
    }

    private function formatQueryDate(string $date): string
    {
        return (new DateTimeImmutable($date))->format('d/m/Y');
    }

    private function parseDate(mixed $value, Indicator $indicator): string
    {
        if (! is_string($value)) {
            throw $this->invalidPoint($indicator);
        }

        $date = DateTimeImmutable::createFromFormat('!d/m/Y', $value);

        if ($date === false || $date->format('d/m/Y') !== $value) {
            throw $this->invalidPoint($indicator);
        }

        $databaseDate = $date->format('Y-m-d');

        if ($databaseDate < config('sgs.start_date') || $databaseDate > config('sgs.end_date')) {
            throw $this->invalidPoint($indicator);
        }

        return $databaseDate;
    }

    private function parseValue(mixed $value, Indicator $indicator): string
    {
        if (! is_string($value) || preg_match('/^-?\d{1,6}(?:\.\d{1,4})?$/', $value) !== 1) {
            throw $this->invalidPoint($indicator);
        }

        return $value;
    }

    private function invalidPoint(Indicator $indicator): SgsSynchronizationException
    {
        return new SgsSynchronizationException(
            "A série SGS {$indicator->sgs_code} contém um ponto inválido.",
        );
    }
}
