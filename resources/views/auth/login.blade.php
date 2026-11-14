@extends('layouts.app')

@section('title', 'Entrar | Painel de Indicadores')

@section('content')
<main class="container min-vh-100 d-flex align-items-center justify-content-center py-5">
    <div class="card border-0 shadow-sm w-100" style="max-width: 420px">
        <div class="card-body p-4 p-md-5">
            <h1 class="h3 mb-2">Painel de Indicadores</h1>
            <p class="text-secondary mb-4">Entre para consultar IPCA e IGP-M.</p>

            <form method="POST" action="{{ route('login.store') }}">
                @csrf

                <div class="mb-3">
                    <label for="email" class="form-label">E-mail</label>
                    <input
                        type="email"
                        class="form-control @error('email') is-invalid @enderror"
                        id="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        autocomplete="email"
                    >
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label">Senha</label>
                    <input
                        type="password"
                        class="form-control @error('password') is-invalid @enderror"
                        id="password"
                        name="password"
                        required
                        autocomplete="current-password"
                    >
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary w-100">Entrar</button>
            </form>
        </div>
    </div>
</main>
@endsection
