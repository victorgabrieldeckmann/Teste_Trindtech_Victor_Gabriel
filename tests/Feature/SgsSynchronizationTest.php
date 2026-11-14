<?php

namespace Tests\Feature;

use App\Exceptions\SgsSynchronizationException;
use App\Models\Indicator;
use App\Services\Sgs\SgsSynchronizationService;
use Closure;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\IndicatorSeeder;
use Database\Seeders\OfflineIndicatorValuesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SgsSynchronizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    public function test_it_requests_the_fixed_period_and_stores_the_sgs_points(): void
    {
        $indicator = $this->createIndicator();

        Http::fake([
            '*' => Http::response([
                ['data' => '01/01/2025', 'valor' => '0.16'],
                ['data' => '01/08/2025', 'valor' => '-0.11'],
            ]),
        ]);

        $processed = $this->synchronizer()->synchronize($indicator);

        $this->assertSame(2, $processed);
        $this->assertDatabaseHas('indicator_values', [
            'indicator_id' => $indicator->id,
            'reference_date' => '2025-01-01',
            'value' => '0.1600',
        ]);
        $this->assertDatabaseHas('indicator_values', [
            'indicator_id' => $indicator->id,
            'reference_date' => '2025-08-01',
            'value' => '-0.1100',
        ]);

        Http::assertSent(function (Request $request) use ($indicator): bool {
            return str_starts_with(
                $request->url(),
                config('sgs.base_url').".{$indicator->sgs_code}/dados?",
            )
                && $request['formato'] === 'json'
                && $request['dataInicial'] === '01/01/2025'
                && $request['dataFinal'] === '01/11/2026';
        });
    }

    public function test_synchronizing_again_updates_the_value_without_duplicating_the_date(): void
    {
        $indicator = $this->createIndicator();

        $this->synchronizer()->synchronizeFromPayload($indicator, [
            ['data' => '01/09/2026', 'valor' => '0.82'],
        ]);
        $this->synchronizer()->synchronizeFromPayload($indicator, [
            ['data' => '01/09/2026', 'valor' => '0.99'],
        ]);

        $this->assertDatabaseCount('indicator_values', 1);
        $this->assertDatabaseHas('indicator_values', [
            'indicator_id' => $indicator->id,
            'reference_date' => '2026-09-01',
            'value' => '0.9900',
        ]);
    }

    public function test_an_http_failure_preserves_existing_values(): void
    {
        $indicator = $this->createIndicator();
        $indicator->values()->create([
            'reference_date' => '2025-01-01',
            'value' => '0.1600',
        ]);

        Http::fake(['*' => Http::response([], 503)]);

        $this->assertSynchronizationFails(
            fn () => $this->synchronizer()->synchronize($indicator),
        );

        $this->assertDatabaseHas('indicator_values', [
            'indicator_id' => $indicator->id,
            'reference_date' => '2025-01-01',
            'value' => '0.1600',
        ]);
    }

    public function test_invalid_json_preserves_existing_values(): void
    {
        $indicator = $this->createIndicator();
        $indicator->values()->create([
            'reference_date' => '2025-01-01',
            'value' => '0.1600',
        ]);

        Http::fake(['*' => Http::response('{invalid-json')]);

        $this->assertSynchronizationFails(
            fn () => $this->synchronizer()->synchronize($indicator),
        );

        $this->assertDatabaseCount('indicator_values', 1);
    }

    public function test_invalid_points_are_rejected_before_the_upsert(): void
    {
        $indicator = $this->createIndicator();
        $invalidPayloads = [
            [],
            [['data' => '31/02/2025', 'valor' => '0.16']],
            [['data' => '01/12/2026', 'valor' => '0.16']],
            [['data' => '01/01/2025', 'valor' => 'valor inválido']],
            [
                ['data' => '01/01/2025', 'valor' => '0.16'],
                ['data' => '01/01/2025', 'valor' => '0.17'],
            ],
        ];

        foreach ($invalidPayloads as $payload) {
            $this->assertSynchronizationFails(
                fn () => $this->synchronizer()->synchronizeFromPayload($indicator, $payload),
            );
        }

        $this->assertDatabaseCount('indicator_values', 0);
    }

    public function test_the_offline_seeder_imports_the_official_fixtures(): void
    {
        $this->seed(IndicatorSeeder::class);
        $this->seed(OfflineIndicatorValuesSeeder::class);

        $this->assertDatabaseCount('indicator_values', 44);
        $this->assertDatabaseHas('indicator_values', [
            'reference_date' => '2026-09-01',
            'value' => '0.8200',
        ]);
        $this->assertDatabaseHas('indicator_values', [
            'reference_date' => '2025-06-01',
            'value' => '-1.6700',
        ]);
    }

    public function test_the_database_seeder_supports_a_complete_offline_installation(): void
    {
        config(['sgs.seed_mode' => 'offline']);

        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('indicators', 2);
        $this->assertDatabaseCount('indicator_values', 44);
        Http::assertNothingSent();
    }

    public function test_the_command_synchronizes_all_persisted_indicators(): void
    {
        $this->seed(IndicatorSeeder::class);

        Http::fake([
            '*' => Http::response([
                ['data' => '01/01/2025', 'valor' => '0.16'],
            ]),
        ]);

        $this->artisan('sgs:sync')
            ->expectsOutputToContain('1 pontos processados')
            ->assertSuccessful();

        $this->assertDatabaseCount('indicator_values', 2);
        Http::assertSentCount(2);
    }

    private function createIndicator(): Indicator
    {
        return Indicator::query()->create([
            'sgs_code' => 433,
            'name' => 'IPCA',
        ]);
    }

    private function synchronizer(): SgsSynchronizationService
    {
        return app(SgsSynchronizationService::class);
    }

    private function assertSynchronizationFails(Closure $callback): void
    {
        try {
            $callback();
            $this->fail('A sincronização deveria ter falhado.');
        } catch (SgsSynchronizationException $exception) {
            $this->assertNotSame('', $exception->getMessage());
        }
    }
}
