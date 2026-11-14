<?php

namespace Tests\Feature;

use App\Models\Indicator;
use App\Models\IndicatorValue;
use Database\Seeders\IndicatorSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class IndicatorPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_initial_indicators_are_seeded_without_duplicates(): void
    {
        $this->seed(IndicatorSeeder::class);
        $this->seed(IndicatorSeeder::class);

        $this->assertDatabaseCount('indicators', 2);
        $this->assertDatabaseHas('indicators', [
            'sgs_code' => 433,
            'name' => 'IPCA',
        ]);
        $this->assertDatabaseHas('indicators', [
            'sgs_code' => 189,
            'name' => 'IGP-M',
        ]);
    }

    public function test_an_indicator_has_values_and_a_value_belongs_to_an_indicator(): void
    {
        $indicator = Indicator::query()->create([
            'sgs_code' => 433,
            'name' => 'IPCA',
        ]);

        $value = $indicator->values()->create([
            'reference_date' => '2026-09-01',
            'value' => '0.8200',
        ]);

        $this->assertTrue($indicator->is($value->indicator));
        $this->assertTrue($value->is($indicator->values->first()));
        $this->assertSame('0.8200', $value->value);
    }

    public function test_the_same_date_is_allowed_for_different_indicators(): void
    {
        $ipca = Indicator::query()->create([
            'sgs_code' => 433,
            'name' => 'IPCA',
        ]);
        $igpm = Indicator::query()->create([
            'sgs_code' => 189,
            'name' => 'IGP-M',
        ]);

        foreach ([$ipca, $igpm] as $indicator) {
            $indicator->values()->create([
                'reference_date' => '2026-09-01',
                'value' => '0.8200',
            ]);
        }

        $this->assertDatabaseCount('indicator_values', 2);
    }

    public function test_the_sgs_code_uniquely_identifies_an_indicator(): void
    {
        Indicator::query()->create([
            'sgs_code' => 433,
            'name' => 'IPCA',
        ]);

        $this->expectException(QueryException::class);

        Indicator::query()->create([
            'sgs_code' => 433,
            'name' => 'Outro nome',
        ]);
    }

    public function test_the_sgs_code_cannot_be_changed_after_creation(): void
    {
        $indicator = Indicator::query()->create([
            'sgs_code' => 433,
            'name' => 'IPCA',
        ]);

        $this->expectException(LogicException::class);

        $indicator->update(['sgs_code' => 189]);
    }

    public function test_the_same_indicator_cannot_have_two_values_on_the_same_date(): void
    {
        $indicator = Indicator::query()->create([
            'sgs_code' => 433,
            'name' => 'IPCA',
        ]);

        $indicator->values()->create([
            'reference_date' => '2026-09-01',
            'value' => '0.8200',
        ]);

        $this->expectException(QueryException::class);

        $indicator->values()->create([
            'reference_date' => '2026-09-01',
            'value' => '0.8300',
        ]);
    }

    public function test_deleting_an_indicator_cascades_only_its_values(): void
    {
        $ipca = Indicator::query()->create([
            'sgs_code' => 433,
            'name' => 'IPCA',
        ]);
        $igpm = Indicator::query()->create([
            'sgs_code' => 189,
            'name' => 'IGP-M',
        ]);

        $ipcaValue = IndicatorValue::query()->create([
            'indicator_id' => $ipca->id,
            'reference_date' => '2026-09-01',
            'value' => '0.8200',
        ]);
        $igpmValue = IndicatorValue::query()->create([
            'indicator_id' => $igpm->id,
            'reference_date' => '2026-09-01',
            'value' => '0.4200',
        ]);

        $ipca->delete();

        $this->assertDatabaseMissing('indicator_values', ['id' => $ipcaValue->id]);
        $this->assertDatabaseHas('indicator_values', ['id' => $igpmValue->id]);
        $this->assertDatabaseHas('indicators', ['id' => $igpm->id]);
    }
}
