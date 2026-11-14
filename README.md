# Painel de Indicadores Econômicos

Teste técnico desenvolvido com Laravel 12, PHP 8.4 e MySQL 8.4.

## Ambiente de desenvolvimento

Requisitos: Docker e Docker Compose.

```bash
cp .env.example .env
docker compose build
docker compose run --rm app php artisan key:generate
docker compose up -d
docker compose exec app php artisan migrate --seed
```

A aplicação fica disponível em `http://localhost:8000`.

### Credenciais de demonstração

- E-mail: `demo@trindtech.test`
- Senha: `Trindtech@2026`

A senha é transformada em hash pelo `DemoUserSeeder` antes de ser armazenada.

## Uso do painel

Após o login, a tela inicial permite:

- alterar apenas o nome exibido de cada indicador;
- sincronizar uma série com o SGS do Banco Central;
- excluir um indicador e seus próprios valores;
- restaurar IPCA e IGP-M caso ambos tenham sido excluídos;
- filtrar a consulta por indicador, data inicial e data final;
- consultar os mesmos pontos na tabela e no gráfico de linha;
- visualizar o acumulado, a maior e a menor variação do período filtrado.

No primeiro acesso, o IPCA e o período completo ficam selecionados. Se o IPCA
for excluído, o painel seleciona o indicador restante. Sem indicadores ou sem
valores no período, a tela apresenta um estado vazio. No estado sem indicadores,
o botão de restauração recria as séries padrão e tenta sincronizá-las novamente.

## Testes

```bash
docker compose exec app php artisan test
```

Os testes usam o banco MySQL isolado `trindtech_indicators_testing`, criado
automaticamente na primeira inicialização do contêiner. O banco de
desenvolvimento não é apagado pela suíte.

## Sincronização com o Banco Central

A instalação padrão consulta as séries IPCA 433 e IGP-M 189 diretamente na
API SGS durante o `migrate --seed`. As consultas sempre usam o período fixo de
`01/01/2025` a `01/11/2026`.

Para sincronizar novamente os dois indicadores:

```bash
docker compose exec app php artisan sgs:sync
```

Também é possível sincronizar apenas uma série já cadastrada:

```bash
docker compose exec app php artisan sgs:sync 433
```

### Carga inicial offline

Se a API SGS estiver indisponível, altere `SGS_SEED_MODE=offline` no `.env`
antes de executar `migrate --seed`. Essa opção usa as respostas oficiais
armazenadas em `database/data/sgs` e passa pelo mesmo validador da carga online.

Use `SGS_SEED_MODE=skip` somente quando quiser cadastrar usuário e indicadores
sem carregar valores.

### Por que o upsert não duplica pontos?

A migration cria uma restrição única em `indicator_id` e `reference_date`.
O serviço usa essa mesma combinação no `upsert`: se a data ainda não existe
para a série, insere; se já existe, atualiza somente o valor e `updated_at`.
Séries diferentes podem ter a mesma data. A chave estrangeira possui exclusão
em cascata, então excluir um indicador remove apenas os valores vinculados a ele.

> Este README será complementado nas próximas etapas com o uso do painel e a
> revisão final da entrega.
