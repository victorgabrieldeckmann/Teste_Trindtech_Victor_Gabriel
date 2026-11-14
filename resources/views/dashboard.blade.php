@extends('layouts.app')

@section('content')
<nav class="navbar bg-white border-bottom sticky-top">
    <div class="container">
        <span class="navbar-brand mb-0 h1">Painel de Indicadores</span>

        <div class="d-flex align-items-center gap-3">
            <span class="text-secondary small d-none d-md-inline">{{ auth()->user()->name }}</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-outline-secondary btn-sm">Sair</button>
            </form>
        </div>
    </div>
</nav>

<main class="container py-4 py-lg-5">
    <x-flash-notifications />

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <header class="mb-4">
        <h1 class="h2 mb-2">Indicadores econômicos</h1>
        <p class="text-secondary mb-0">Gerencie e consulte as séries oficiais do Banco Central.</p>
    </header>

    <section class="mb-5" aria-labelledby="management-title">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 id="management-title" class="h4 mb-0">Gerenciamento</h2>
            <span class="badge text-bg-secondary">{{ $indicators->count() }} indicadores</span>
        </div>

        <div class="row g-3">
            @forelse ($indicators as $indicator)
                <div class="col-lg-6">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                                <div>
                                    <h3 class="h5 mb-1">{{ $indicator->name }}</h3>
                                    <span class="text-secondary small">
                                        Série SGS {{ $indicator->sgs_code }} · {{ $indicator->values_count }} pontos
                                    </span>
                                </div>

                                <button
                                    type="button"
                                    class="btn btn-outline-danger btn-sm"
                                    data-bs-toggle="modal"
                                    data-bs-target="#delete-indicator-modal"
                                    data-delete-name="{{ $indicator->name }}"
                                    data-delete-url="{{ route('indicators.destroy', $indicator) }}"
                                >
                                    Excluir
                                </button>
                            </div>

                            <form method="POST" action="{{ route('indicators.update', $indicator) }}" class="row g-2 mb-2">
                                @csrf
                                @method('PATCH')
                                <div class="col">
                                    <label for="name-{{ $indicator->id }}" class="visually-hidden">Nome exibido</label>
                                    <input type="text" class="form-control form-control-sm" id="name-{{ $indicator->id }}" name="name" value="{{ $indicator->name }}" maxlength="100" required>
                                </div>
                                <div class="col-auto">
                                    <button type="submit" class="btn btn-outline-primary btn-sm">Salvar nome</button>
                                </div>
                            </form>

                            <form method="POST" action="{{ route('indicators.synchronize', $indicator) }}">
                                @csrf
                                <button type="submit" class="btn btn-primary btn-sm w-100">Sincronizar com o SGS</button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center py-4">
                            <h3 class="h5">Nenhum indicador cadastrado</h3>
                            <p class="text-secondary">Restaure as séries padrão para voltar a consultar os dados.</p>
                            <form method="POST" action="{{ route('indicators.restore') }}">
                                @csrf
                                <button type="submit" class="btn btn-primary">Restaurar IPCA e IGP-M</button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforelse
        </div>
    </section>

    <section aria-labelledby="query-title">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <h2 id="query-title" class="h4 mb-3">Consulta</h2>

                @if ($indicators->isNotEmpty())
                    <form method="GET" action="{{ route('dashboard') }}" class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label for="indicator_id" class="form-label">Indicador</label>
                            <select class="form-select" id="indicator_id" name="indicator_id" required>
                                @foreach ($indicators as $indicator)
                                    <option value="{{ $indicator->id }}" @selected($selectedIndicator?->is($indicator))>
                                        {{ $indicator->name }} (SGS {{ $indicator->sgs_code }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="start_date" class="form-label">Data inicial</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" value="{{ $startDate }}" min="{{ config('sgs.start_date') }}" max="{{ config('sgs.end_date') }}" required>
                        </div>
                        <div class="col-md-3">
                            <label for="end_date" class="form-label">Data final</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" value="{{ $endDate }}" min="{{ config('sgs.start_date') }}" max="{{ config('sgs.end_date') }}" required>
                        </div>
                        <div class="col-md-2 d-grid">
                            <button type="submit" class="btn btn-primary">Filtrar</button>
                        </div>
                    </form>
                @else
                    <p class="text-secondary mb-0">Restaure os indicadores padrão para realizar uma consulta.</p>
                @endif
            </div>
        </div>

        @if ($selectedIndicator)
            @if ($summary)
                <div class="row g-3 mb-4" aria-label="Resumo do período filtrado">
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <span class="text-secondary small">Acumulado no período</span>
                                <p class="h3 mb-0 mt-1">{{ number_format($summary['accumulated'], 2, ',', '.') }}%</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 border-start border-4 border-info shadow-sm h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-secondary small">Maior variação</span>
                                    <span class="badge text-bg-info">Maior</span>
                                </div>
                                <p class="h3 mb-1 mt-1">{{ number_format((float) $summary['maximum']->value, 2, ',', '.') }}%</p>
                                <span class="text-secondary small">{{ $summary['maximum']->reference_date->format('d/m/Y') }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 border-start border-4 border-warning shadow-sm h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-secondary small">Menor variação</span>
                                    <span class="badge text-bg-warning">Menor</span>
                                </div>
                                <p class="h3 mb-1 mt-1">{{ number_format((float) $summary['minimum']->value, 2, ',', '.') }}%</p>
                                <span class="text-secondary small">{{ $summary['minimum']->reference_date->format('d/m/Y') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="row g-4">
                <div class="col-xl-7">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h2 class="h5 mb-0">Evolução de {{ $selectedIndicator->name }}</h2>
                                <span class="badge text-bg-light">{{ $points->count() }} pontos</span>
                            </div>

                            @if ($points->isNotEmpty())
                                <div style="height: 340px">
                                    <canvas id="indicator-chart" aria-label="Gráfico de {{ $selectedIndicator->name }}" role="img"></canvas>
                                </div>
                            @else
                                <div class="alert alert-light border mb-0">Nenhum valor encontrado para o período informado.</div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-xl-5">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-0">
                            <div class="p-3 border-bottom">
                                <h2 class="h5 mb-0">Valores</h2>
                            </div>

                            @if ($points->isNotEmpty())
                                <div class="table-responsive" style="max-height: 390px">
                                    <table class="table table-striped table-hover mb-0 align-middle">
                                        <thead class="table-light sticky-top">
                                            <tr>
                                                <th scope="col">Data</th>
                                                <th scope="col" class="text-end">Valor (%)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($points as $point)
                                                @php
                                                    $isMaximum = $point->is($summary['maximum']);
                                                    $isMinimum = $point->is($summary['minimum']);
                                                @endphp
                                                <tr @class([
                                                    'table-info' => $isMaximum,
                                                    'table-warning' => $isMinimum && ! $isMaximum,
                                                ])>
                                                    <td>
                                                        {{ $point->reference_date->format('d/m/Y') }}
                                                        @if ($isMaximum)
                                                            <span class="badge text-bg-info ms-1">Maior</span>
                                                        @endif
                                                        @if ($isMinimum)
                                                            <span class="badge text-bg-warning ms-1">Menor</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-end">{{ number_format((float) $point->value, 2, ',', '.') }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="p-3 text-secondary">Sem dados para exibir.</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </section>
</main>

<x-delete-indicator-modal />
@endsection

@if ($selectedIndicator && $points->isNotEmpty())
    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
        <script>
            const chartPoints = @json($points->map(fn ($point) => [
                'date' => $point->reference_date->format('d/m/Y'),
                'value' => (float) $point->value,
            ])->values());

            new Chart(document.getElementById('indicator-chart'), {
                type: 'line',
                data: {
                    labels: chartPoints.map(point => point.date),
                    datasets: [{
                        label: @json($selectedIndicator->name),
                        data: chartPoints.map(point => point.value),
                        borderColor: '#0d6efd',
                        backgroundColor: 'rgba(13, 110, 253, 0.12)',
                        fill: true,
                        tension: 0.25,
                        pointRadius: 3,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            ticks: {
                                callback: value => `${value}%`,
                            },
                        },
                    },
                },
            });
        </script>
    @endpush
@endif
