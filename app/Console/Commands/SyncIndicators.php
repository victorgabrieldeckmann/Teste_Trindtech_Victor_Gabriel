<?php

namespace App\Console\Commands;

use App\Exceptions\SgsSynchronizationException;
use App\Models\Indicator;
use App\Services\Sgs\SgsSynchronizationService;
use Illuminate\Console\Command;

class SyncIndicators extends Command
{
    /** @var string */
    protected $signature = 'sgs:sync {sgs_code? : Código de uma série já cadastrada}';

    /** @var string */
    protected $description = 'Sincroniza os indicadores cadastrados com o SGS do Banco Central';

    public function handle(SgsSynchronizationService $synchronizer): int
    {
        $query = Indicator::query()->orderBy('id');
        $sgsCode = $this->argument('sgs_code');

        if ($sgsCode !== null) {
            $query->where('sgs_code', $sgsCode);
        }

        $indicators = $query->get();

        if ($indicators->isEmpty()) {
            $this->error('Nenhum indicador cadastrado corresponde à solicitação.');

            return self::INVALID;
        }

        $failed = false;

        foreach ($indicators as $indicator) {
            try {
                $points = $synchronizer->synchronize($indicator);
                $this->info("{$indicator->name}: {$points} pontos processados.");
            } catch (SgsSynchronizationException $exception) {
                $failed = true;
                $this->error($exception->getMessage());
            }
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
