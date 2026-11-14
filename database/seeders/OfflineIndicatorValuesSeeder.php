<?php

namespace Database\Seeders;

use App\Models\Indicator;
use App\Services\Sgs\SgsSynchronizationService;
use Illuminate\Database\Seeder;
use JsonException;
use RuntimeException;

class OfflineIndicatorValuesSeeder extends Seeder
{
    public function run(SgsSynchronizationService $synchronizer): void
    {
        foreach ([433, 189] as $sgsCode) {
            $indicator = Indicator::query()->where('sgs_code', $sgsCode)->firstOrFail();
            $payload = $this->loadFixture($sgsCode);

            $synchronizer->synchronizeFromPayload($indicator, $payload);
        }
    }

    /**
     * @return array<mixed>
     */
    private function loadFixture(int $sgsCode): array
    {
        $path = database_path("data/sgs/{$sgsCode}.json");
        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new RuntimeException("Não foi possível ler a fixture SGS {$sgsCode}.");
        }

        try {
            $payload = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException("A fixture SGS {$sgsCode} contém JSON inválido.", previous: $exception);
        }

        if (! is_array($payload)) {
            throw new RuntimeException("A fixture SGS {$sgsCode} não contém uma lista.");
        }

        return $payload;
    }
}
