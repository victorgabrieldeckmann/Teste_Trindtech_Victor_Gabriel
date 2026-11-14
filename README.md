# Painel de Indicadores Econômicos

Aplicação web desenvolvida para o teste técnico da Trindtech. O painel autentica
o usuário, importa as séries IPCA e IGP-M do Sistema Gerenciador de Séries
Temporais (SGS) do Banco Central e permite consultar os dados armazenados no
MySQL por período.

## Tecnologias

- PHP 8.4 e Laravel 12;
- MySQL 8.4;
- Blade e Bootstrap 5.3;
- Chart.js 4.4;
- Docker Compose.

Bootstrap e Chart.js são carregados por CDN no navegador. Portanto, a
apresentação visual completa requer acesso à internet mesmo quando a carga
offline do SGS é utilizada.

## Executando o projeto

### Pré-requisitos

- Docker;
- Docker Compose.

### Instalação padrão

Na raiz do repositório, execute:

```bash
cp .env.example .env
docker compose build
docker compose run --rm app php artisan key:generate
docker compose up -d
docker compose exec app php artisan migrate --seed
```

O último comando cria as tabelas, o usuário de demonstração, os dois
indicadores e consulta os dados oficiais no SGS. Depois, acesse
`http://localhost:8000`.

### Credenciais de demonstração

- E-mail: `demo@trindtech.test`
- Senha: `Trindtech@2026`

A senha em texto simples acima existe apenas para acesso de demonstração. O
`DemoUserSeeder` usa o hash de senhas do Laravel antes de armazená-la.

### Carga inicial sem acesso ao SGS

Se a API estiver indisponível, defina a variável abaixo no `.env` antes de
executar as migrations:

```dotenv
SGS_SEED_MODE=offline
```

Em seguida, faça a carga normalmente:

```bash
docker compose exec app php artisan migrate --seed
```

O modo `offline` utiliza as respostas oficiais incluídas em
`database/data/sgs` e passa pelo mesmo processo de validação e persistência da
carga online. A opção `SGS_SEED_MODE=skip` cadastra o usuário e os indicadores
sem carregar valores, sendo destinada a desenvolvimento e testes específicos.

## Funcionalidades

Após o login, o painel permite:

- consultar IPCA (série 433) e IGP-M (série 189);
- filtrar uma série por data inicial e final;
- comparar na tabela e no gráfico exatamente os mesmos pontos lidos do MySQL;
- visualizar o acumulado composto, a maior e a menor variação do período;
- identificar visualmente na tabela os pontos de maior e menor variação;
- alterar somente o nome exibido de um indicador, mantendo seu código SGS;
- sincronizar novamente uma série sem duplicar suas datas;
- excluir um indicador e somente os valores associados a ele;
- restaurar e sincronizar as duas séries padrão quando ambas forem excluídas.

No primeiro acesso, o IPCA e todo o intervalo do teste ficam selecionados. Se o
IPCA for excluído, o indicador restante é selecionado. Um período sem valores e
um painel sem indicadores possuem estados vazios próprios.

Confirmações de exclusão são apresentadas em um modal. Mensagens de sucesso ou
erro aparecem como notificações temporárias no canto superior direito e são
empilhadas quando há mais de uma.

## Sincronização com o Banco Central

A fonte dos dados é a API pública SGS do Banco Central. As consultas usam o
intervalo fixo de `01/01/2025` a `01/11/2026` definido no teste:

- IPCA: série 433;
- IGP-M: série 189.

Endpoints oficiais utilizados:

- [IPCA — série 433](https://api.bcb.gov.br/dados/serie/bcdata.sgs.433/dados?formato=json&dataInicial=01/01/2025&dataFinal=01/11/2026);
- [IGP-M — série 189](https://api.bcb.gov.br/dados/serie/bcdata.sgs.189/dados?formato=json&dataInicial=01/01/2025&dataFinal=01/11/2026).

Para sincronizar todas as séries cadastradas:

```bash
docker compose exec app php artisan sgs:sync
```

Para sincronizar apenas uma série, informe seu código SGS:

```bash
docker compose exec app php artisan sgs:sync 433
```

O serviço valida o formato da resposta, as datas, os valores e a presença de
datas repetidas antes de persistir o lote. O `upsert` insere ou atualiza os
pontos recebidos e remove, dentro do período fixo, os pontos que não estão mais
na resposta atual. Uma falha de conexão, resposta inválida ou erro de
persistência gera uma mensagem e preserva os dados anteriores. Durante a carga
online inicial, as duas séries são persistidas em uma única transação para não
deixar uma importação parcial.

## Modelo de dados e persistência

O domínio utiliza três tabelas principais:

| Tabela | Dados principais | Regra relevante |
| --- | --- | --- |
| `users` | nome, e-mail e senha | e-mail único e senha com hash |
| `indicators` | código SGS e nome | código SGS único |
| `indicator_values` | indicador, data e valor | uma linha por indicador e data |

`indicator_values.indicator_id` é uma chave estrangeira para `indicators.id`
com exclusão em cascata. Assim, o MySQL remove os pontos do indicador excluído,
sem afetar outra série.

A restrição única composta em `(indicator_id, reference_date)` permite que IPCA
e IGP-M tenham registros na mesma data, mas impede duas linhas da mesma série
para aquela data. Essa combinação também é usada pelo `upsert`: uma data nova é
inserida e uma data existente tem seu valor atualizado.

Datas são armazenadas como `DATE`. Os valores usam `DECIMAL(10,4)`, evitando a
imprecisão binária de tipos de ponto flutuante na persistência.

## Organização da aplicação

- `app/Http/Controllers`: autenticação, painel e ações dos indicadores;
- `app/Http/Requests`: validação dos filtros e da edição do nome;
- `app/Services/Sgs`: integração, validação e persistência dos dados do SGS;
- `database/migrations`: estrutura e integridade do banco;
- `database/seeders`: usuário, indicadores e cargas online/offline;
- `resources/views`: telas Blade e componentes de modal e notificações;
- `tests`: testes automatizados de integração e regras da aplicação.

As rotas de consulta e gerenciamento são protegidas pelo middleware `auth`. O
controller do painel busca uma única coleção ordenada de pontos e a envia tanto
para a tabela quanto para o Chart.js, garantindo que ambos representem o mesmo
indicador e período.

## Testes e qualidade

Execute a suíte automatizada com:

```bash
docker compose exec app php artisan test
```

Os testes usam o banco MySQL isolado `trindtech_indicators_testing`, criado na
primeira inicialização do contêiner. O banco de desenvolvimento não é apagado
pela suíte.

Para verificar o padrão de código:

```bash
docker compose exec app ./vendor/bin/pint --test
```

A suíte cobre autenticação e acesso protegido, integridade do modelo, filtros,
resumos, gerenciamento e restauração dos indicadores, sincronização, `upsert`,
carga offline, falhas do SGS e correspondência dos dados da tabela com o
gráfico.

## Comandos úteis

```bash
# acompanhar os logs
docker compose logs -f app

# interromper os contêineres
docker compose down

# recriar o banco de desenvolvimento e executar a carga inicial
docker compose exec app php artisan migrate:fresh --seed
```

O último comando apaga os dados do banco de desenvolvimento antes de recriá-lo.

## Solução de problemas

- **Porta ocupada:** libere a porta `8000` ou altere seu mapeamento em
  `compose.yaml`. O MySQL é acessível apenas pela rede interna do Compose.
- **SGS indisponível na instalação:** selecione `SGS_SEED_MODE=offline` e repita
  `php artisan migrate:fresh --seed` dentro do contêiner.
- **Estilos ou gráfico não carregam:** verifique o acesso do navegador ao CDN
  jsDelivr.
- **Configuração alterada no `.env`:** execute
  `docker compose exec app php artisan config:clear` antes de tentar novamente.

## Licença

Este projeto é disponibilizado sob a [licença MIT](LICENSE).
