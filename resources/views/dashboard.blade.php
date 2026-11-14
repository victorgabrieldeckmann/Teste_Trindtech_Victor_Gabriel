@extends('layouts.app')

@section('content')
<nav class="navbar bg-white border-bottom">
    <div class="container">
        <span class="navbar-brand mb-0 h1">Painel de Indicadores</span>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-outline-secondary btn-sm">Sair</button>
        </form>
    </div>
</nav>

<main class="container py-5">
    <div class="mb-4">
        <h1 class="h2">Indicadores econômicos</h1>
        <p class="text-secondary mb-0">
            Olá, {{ auth()->user()->name }}. A consulta dos valores será adicionada na próxima etapa.
        </p>
    </div>

    <div class="row g-3">
        @forelse ($indicators as $indicator)
            <div class="col-md-6">
                <div class="card h-100 shadow-sm border-0">
                    <div class="card-body">
                        <h2 class="h5 mb-1">{{ $indicator->name }}</h2>
                        <span class="text-secondary">Série SGS {{ $indicator->sgs_code }}</span>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-info mb-0">Nenhum indicador cadastrado.</div>
            </div>
        @endforelse
    </div>
</main>
@endsection
