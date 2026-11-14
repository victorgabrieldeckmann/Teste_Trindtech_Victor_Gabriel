<?php

namespace Tests\Feature;

use App\Models\Indicator;
use App\Models\IndicatorValue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_dashboard_actions_require_authentication(): void
    {
        $indicator = $this->createIndicator(433, 'IPCA');

        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->patch(route('indicators.update', $indicator), ['name' => 'Novo nome'])
            ->assertRedirect(route('login'));
        $this->delete(route('indicators.destroy', $indicator))
            ->assertRedirect(route('login'));
        $this->post(route('indicators.synchronize', $indicator))
            ->assertRedirect(route('login'));
        $this->post(route('indicators.restore'))
            ->assertRedirect(route('login'));
    }

    public function test_first_access_selects_ipca_and_the_complete_period(): void
    {
        $igpm = $this->createIndicator(189, 'IGP-M');
        $ipca = $this->createIndicator(433, 'IPCA');
        $this->createValue($ipca, '2025-01-01', '0.1600');
        $this->createValue($ipca, '2026-10-01', '0.1400');
        $this->createValue($igpm, '2025-01-01', '0.2700');

        $this->actingAs(User::factory()->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('selectedIndicator', fn (Indicator $indicator) => $indicator->is($ipca))
            ->assertViewHas('startDate', '2025-01-01')
            ->assertViewHas('endDate', '2026-11-01')
            ->assertViewHas('points', fn ($points) => $points->count() === 2)
            ->assertSee('01/01/2025')
            ->assertSee('01/10/2026');
    }

    public function test_filters_limit_the_same_points_used_by_the_table_and_chart(): void
    {
        $indicator = $this->createIndicator(189, 'IGP-M');
        $this->createValue($indicator, '2025-01-01', '0.2700');
        $february = $this->createValue($indicator, '2025-02-01', '1.0600');
        $march = $this->createValue($indicator, '2025-03-01', '-0.3400');
        $this->createValue($indicator, '2025-04-01', '0.2400');

        $response = $this->actingAs(User::factory()->create())->get(route('dashboard', [
            'indicator_id' => $indicator->id,
            'start_date' => '2025-02-01',
            'end_date' => '2025-03-01',
        ]));

        $response
            ->assertOk()
            ->assertViewHas('points', function ($points) use ($february, $march): bool {
                return $points->pluck('id')->all() === [$february->id, $march->id];
            })
            ->assertSee('01/02/2025')
            ->assertSee('01/03/2025')
            ->assertDontSee('01/01/2025')
            ->assertDontSee('01/04/2025')
            ->assertSee('const chartPoints =', escape: false);
    }

    public function test_the_summary_describes_only_the_filtered_points(): void
    {
        $indicator = $this->createIndicator(433, 'IPCA');
        $this->createValue($indicator, '2025-01-01', '9.9900');
        $this->createValue($indicator, '2025-02-01', '1.0000');
        $this->createValue($indicator, '2025-03-01', '-0.5000');
        $this->createValue($indicator, '2025-04-01', '2.0000');

        $this->actingAs(User::factory()->create())
            ->get(route('dashboard', [
                'indicator_id' => $indicator->id,
                'start_date' => '2025-02-01',
                'end_date' => '2025-04-01',
            ]))
            ->assertOk()
            ->assertViewHas('summary', function (array $summary): bool {
                return round($summary['accumulated'], 4) === 2.5049
                    && $summary['maximum']->reference_date->format('Y-m-d') === '2025-04-01'
                    && $summary['maximum']->value === '2.0000'
                    && $summary['minimum']->reference_date->format('Y-m-d') === '2025-03-01'
                    && $summary['minimum']->value === '-0.5000';
            })
            ->assertSee('Acumulado no período')
            ->assertSee('2,50%')
            ->assertSee('Maior variação')
            ->assertSee('2,00%')
            ->assertSee('01/04/2025')
            ->assertSee('Menor variação')
            ->assertSee('-0,50%')
            ->assertSee('border-info', escape: false)
            ->assertSee('border-warning', escape: false)
            ->assertSee('table-info', escape: false)
            ->assertSee('table-warning', escape: false)
            ->assertDontSee('9,99%');
    }

    public function test_an_inverted_period_is_rejected(): void
    {
        $indicator = $this->createIndicator(433, 'IPCA');

        $this->actingAs(User::factory()->create())
            ->from(route('dashboard'))
            ->get(route('dashboard', [
                'indicator_id' => $indicator->id,
                'start_date' => '2026-03-01',
                'end_date' => '2026-02-01',
            ]))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHasErrors('end_date');
    }

    public function test_an_unknown_indicator_filter_is_rejected(): void
    {
        $this->actingAs(User::factory()->create())
            ->from(route('dashboard'))
            ->get(route('dashboard', ['indicator_id' => 999999]))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHasErrors('indicator_id');
    }

    public function test_the_available_indicator_is_selected_when_ipca_does_not_exist(): void
    {
        $igpm = $this->createIndicator(189, 'IGP-M');

        $this->actingAs(User::factory()->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('selectedIndicator', fn (Indicator $indicator) => $indicator->is($igpm));
    }

    public function test_the_dashboard_has_an_empty_state_without_indicators(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('selectedIndicator', null)
            ->assertSee('Nenhum indicador cadastrado')
            ->assertSee('Restaurar IPCA e IGP-M');
    }

    public function test_an_indicator_name_can_be_updated_without_changing_its_sgs_code(): void
    {
        $indicator = $this->createIndicator(433, 'IPCA');

        $this->actingAs(User::factory()->create())
            ->patch(route('indicators.update', $indicator), [
                'name' => 'IPCA mensal',
                'sgs_code' => 999,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('indicators', [
            'id' => $indicator->id,
            'name' => 'IPCA mensal',
            'sgs_code' => 433,
        ]);
    }

    public function test_deletion_uses_the_application_modal_instead_of_a_browser_confirmation(): void
    {
        $indicator = $this->createIndicator(433, 'IPCA');

        $this->actingAs(User::factory()->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('id="delete-indicator-modal"', escape: false)
            ->assertSee('data-delete-name="IPCA"', escape: false)
            ->assertSee('data-delete-url="'.route('indicators.destroy', $indicator).'"', escape: false)
            ->assertDontSee('return confirm', escape: false);
    }

    public function test_flash_notifications_are_queued_as_stacked_autohiding_toasts(): void
    {
        $this->createIndicator(433, 'IPCA');

        $response = $this->actingAs(User::factory()->create())
            ->withSession([
                'success' => 'Indicador restaurado.',
                'error' => 'Não foi possível sincronizar.',
            ])
            ->get(route('dashboard'));

        $response
            ->assertOk()
            ->assertSee('Indicador restaurado.')
            ->assertSessionHas('error', 'Não foi possível sincronizar.')
            ->assertSee('data-flash-toast-container', escape: false)
            ->assertSee('const lifetime = 3000', escape: false)
            ->assertSee('sessionStorage.setItem(storageKey', escape: false)
            ->assertSee('activeNotifications = [...storedNotifications, ...newNotifications]', escape: false);
    }

    public function test_an_indicator_name_is_required_and_limited(): void
    {
        $indicator = $this->createIndicator(433, 'IPCA');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('dashboard'))
            ->patch(route('indicators.update', $indicator), ['name' => ''])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHasErrors('name');

        $this->actingAs($user)
            ->from(route('dashboard'))
            ->patch(route('indicators.update', $indicator), ['name' => str_repeat('a', 101)])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHasErrors('name');
    }

    public function test_deleting_an_indicator_removes_only_its_values(): void
    {
        $ipca = $this->createIndicator(433, 'IPCA');
        $igpm = $this->createIndicator(189, 'IGP-M');
        $ipcaValue = $this->createValue($ipca, '2025-01-01', '0.1600');
        $igpmValue = $this->createValue($igpm, '2025-01-01', '0.2700');

        $this->actingAs(User::factory()->create())
            ->delete(route('indicators.destroy', $ipca))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('indicators', ['id' => $ipca->id]);
        $this->assertDatabaseMissing('indicator_values', ['id' => $ipcaValue->id]);
        $this->assertDatabaseHas('indicators', ['id' => $igpm->id]);
        $this->assertDatabaseHas('indicator_values', ['id' => $igpmValue->id]);
    }

    public function test_synchronization_action_imports_points_and_shows_success(): void
    {
        $indicator = $this->createIndicator(433, 'IPCA');
        Http::fake([
            '*' => Http::response([
                ['data' => '01/01/2025', 'valor' => '0.16'],
            ]),
        ]);

        $this->actingAs(User::factory()->create())
            ->post(route('indicators.synchronize', $indicator))
            ->assertRedirect()
            ->assertSessionHas('success', 'IPCA sincronizado: 1 ponto processado.');

        $this->assertDatabaseHas('indicator_values', [
            'indicator_id' => $indicator->id,
            'reference_date' => '2025-01-01',
            'value' => '0.1600',
        ]);
    }

    public function test_synchronization_failure_is_reported_and_preserves_data(): void
    {
        $indicator = $this->createIndicator(433, 'IPCA');
        $value = $this->createValue($indicator, '2025-01-01', '0.1600');
        Http::fake(['*' => Http::response([], 503)]);

        $this->actingAs(User::factory()->create())
            ->post(route('indicators.synchronize', $indicator))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('indicator_values', [
            'id' => $value->id,
            'value' => '0.1600',
        ]);
    }

    public function test_an_empty_filter_result_displays_the_empty_state_without_a_chart(): void
    {
        $indicator = $this->createIndicator(433, 'IPCA');

        $this->actingAs(User::factory()->create())
            ->get(route('dashboard', [
                'indicator_id' => $indicator->id,
                'start_date' => '2026-01-01',
                'end_date' => '2026-02-01',
            ]))
            ->assertOk()
            ->assertSee('Nenhum valor encontrado para o período informado')
            ->assertSee('Sem dados para exibir')
            ->assertDontSee('chart.umd.min.js');
    }

    public function test_default_indicators_can_be_restored_and_synchronized(): void
    {
        Http::fake([
            '*' => Http::response([
                ['data' => '01/01/2025', 'valor' => '0.16'],
            ]),
        ]);

        $this->actingAs(User::factory()->create())
            ->post(route('indicators.restore'))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('success', 'Indicadores padrão restaurados: 2 pontos processados.');

        $this->assertDatabaseHas('indicators', ['sgs_code' => 433, 'name' => 'IPCA']);
        $this->assertDatabaseHas('indicators', ['sgs_code' => 189, 'name' => 'IGP-M']);
        $this->assertDatabaseCount('indicator_values', 2);
        Http::assertSentCount(2);
    }

    public function test_restoration_keeps_indicators_available_when_the_sgs_fails(): void
    {
        Http::fake(['*' => Http::response([], 503)]);

        $this->actingAs(User::factory()->create())
            ->post(route('indicators.restore'))
            ->assertRedirect(route('dashboard'))
            ->assertSessionMissing('success')
            ->assertSessionHas(
                'error',
                'Os indicadores foram restaurados, mas não foi possível sincronizar agora: IPCA, IGP-M.',
            );

        $this->assertDatabaseCount('indicators', 2);
        $this->assertDatabaseCount('indicator_values', 0);
    }

    public function test_restoration_creates_only_missing_indicators_and_preserves_existing_names(): void
    {
        $existing = $this->createIndicator(433, 'IPCA personalizado');
        $this->createValue($existing, '2025-01-01', '0.1600');
        Http::fake([
            '*' => Http::response([
                ['data' => '01/01/2025', 'valor' => '0.27'],
            ]),
        ]);

        $this->actingAs(User::factory()->create())
            ->post(route('indicators.restore'))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('success', 'Indicadores padrão restaurados: 1 ponto processado.');

        $this->assertDatabaseCount('indicators', 2);
        $this->assertDatabaseHas('indicators', [
            'id' => $existing->id,
            'name' => 'IPCA personalizado',
        ]);
        $this->assertDatabaseCount('indicator_values', 2);
        Http::assertSentCount(1);
    }

    private function createIndicator(int $sgsCode, string $name): Indicator
    {
        return Indicator::query()->create([
            'sgs_code' => $sgsCode,
            'name' => $name,
        ]);
    }

    private function createValue(
        Indicator $indicator,
        string $referenceDate,
        string $value,
    ): IndicatorValue {
        return $indicator->values()->create([
            'reference_date' => $referenceDate,
            'value' => $value,
        ]);
    }
}
