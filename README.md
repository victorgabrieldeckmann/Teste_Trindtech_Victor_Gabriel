# Painel de Indicadores Econômicos

Aplicação do teste técnico da Trindtech para consultar IPCA e IGP-M. Os dados
são obtidos na API SGS do Banco Central, armazenados no MySQL e exibidos em uma
tabela e em um gráfico de linha.

## Tecnologias

- PHP 8.4 e Laravel 12;
- MySQL 8.4;
- Blade, Bootstrap 5.3 e Chart.js 4.4;
- Docker Compose.

## Executando o projeto

### Pré-requisitos

- Docker;
- Docker Compose;
- acesso à internet para baixar imagens e dependências.

Na raiz do repositório, execute:

```bash
cp .env.example .env
docker compose build
docker compose run --rm app php artisan key:generate
docker compose up -d
docker compose exec app php artisan migrate --seed
```

O último comando cria o banco, o usuário de demonstração, os indicadores e
consulta os dados oficiais no SGS. A aplicação estará disponível em
`http://localhost:8000`.

### Credenciais de demonstração

- E-mail: `demo@trindtech.test`
- Senha: `Trindtech@2026`

A senha é armazenada como hash pelo Laravel. Não há cadastro de novos usuários.

### Carga inicial sem acesso ao SGS

Se a API estiver indisponível, altere a seguinte variável no `.env`:

```dotenv
SGS_SEED_MODE=offline
```

Depois, execute:

```bash
docker compose exec app php artisan migrate --seed
```

Esse modo utiliza as respostas oficiais incluídas em `database/data/sgs` e
passa pela mesma validação da carga online. Ele dispensa apenas o acesso ao SGS;
o download inicial das imagens Docker ainda requer internet.

## Funcionalidades

Após o login, o painel permite:

- consultar IPCA (série 433) e IGP-M (série 189);
- filtrar por indicador, data inicial e data final;
- visualizar os mesmos pontos do MySQL na tabela e no gráfico;
- consultar o acumulado, a maior e a menor variação do período;
- alterar somente o nome exibido, mantendo o código SGS;
- sincronizar novamente uma série sem duplicar datas;
- excluir um indicador e somente os valores vinculados a ele.

No primeiro acesso, o IPCA e o período completo ficam selecionados. Se todos os
indicadores forem excluídos, o painel permite restaurar IPCA e IGP-M.

## Sincronização com o Banco Central

As duas séries usam o período fixo de `01/01/2025` a `01/11/2026`:

- [IPCA — série 433](https://api.bcb.gov.br/dados/serie/bcdata.sgs.433/dados?formato=json&dataInicial=01/01/2025&dataFinal=01/11/2026);
- [IGP-M — série 189](https://api.bcb.gov.br/dados/serie/bcdata.sgs.189/dados?formato=json&dataInicial=01/01/2025&dataFinal=01/11/2026).

Para sincronizar todas as séries cadastradas:

```bash
docker compose exec app php artisan sgs:sync
```

Para sincronizar somente uma série:

```bash
docker compose exec app php artisan sgs:sync 433
```

O serviço valida a resposta antes de gravá-la. O `upsert` insere datas novas e
atualiza valores existentes sem criar duplicações. Falhas de conexão ou
respostas inválidas preservam os dados já armazenados.

## Modelo de dados

- `users`: e-mail único e senha armazenada como hash;
- `indicators`: código SGS único e nome exibido;
- `indicator_values`: data em `DATE`, valor em `DECIMAL(10,4)` e vínculo com o
  indicador.

A combinação `(indicator_id, reference_date)` é única e também identifica os
registros atualizados pelo `upsert`. A chave estrangeira possui exclusão em
cascata, portanto excluir um indicador remove somente os valores daquela série.

## Testes

```bash
docker compose exec app php artisan test
docker compose exec app ./vendor/bin/pint --test
```

Os testes usam o banco isolado `trindtech_indicators_testing`; o banco de
desenvolvimento não é apagado pela suíte.

## Comandos úteis

```bash
# acompanhar os logs da aplicação
docker compose logs -f app

# interromper os contêineres
docker compose down

# recriar o banco de desenvolvimento e repetir a carga inicial
docker compose exec app php artisan migrate:fresh --seed
```

`migrate:fresh` apaga os dados atuais antes de recriar o banco.

## Solução de problemas

- **Porta 8000 ocupada:** libere a porta ou altere seu mapeamento em
  `compose.yaml`.
- **SGS indisponível:** use `SGS_SEED_MODE=offline` e repita a carga inicial.
- **Estilos ou gráfico não carregam:** Bootstrap e Chart.js são carregados pelo
  CDN jsDelivr e dependem do acesso do navegador à internet.
- **Alteração no `.env` sem efeito:** execute
  `docker compose exec app php artisan config:clear`.

## Licença

Este projeto é disponibilizado sob a [licença MIT](LICENSE).
