# Painel de Indicadores Econômicos

Teste técnico desenvolvido com Laravel 12, PHP 8.4 e MySQL 8.4.

## Ambiente de desenvolvimento

Requisitos: Docker e Docker Compose.

```bash
cp .env.example .env
docker compose build
docker compose run --rm app php artisan key:generate
docker compose up -d
docker compose exec app php artisan migrate
```

A aplicação fica disponível em `http://localhost:8000`.

## Testes

```bash
docker compose exec app php artisan test
```

Os testes usam o banco MySQL isolado `trindtech_indicators_testing`, criado
automaticamente na primeira inicialização do contêiner. O banco de
desenvolvimento não é apagado pela suíte.

> Este README será complementado nas próximas etapas com a carga inicial,
> credenciais de demonstração e explicações do modelo de dados.
