# Fixtures oficiais do SGS

Os arquivos deste diretório são cópias das respostas oficiais usadas como
alternativa quando o SGS do Banco Central está indisponível durante a instalação.
Eles foram verificados em 13/11/2026 e abrangem o período de 01/01/2025 a
01/11/2026.

| Arquivo | Série | Fonte | Registros | SHA-256 |
| --- | --- | --- | ---: | --- |
| `433.json` | IPCA (433) | [API SGS](https://api.bcb.gov.br/dados/serie/bcdata.sgs.433/dados?formato=json&dataInicial=01/01/2025&dataFinal=01/11/2026) | 22 | `cba60ab36f9641d8d5d24e32b9c08f190f81ff6b2066e6b017dd13d06cd412a6` |
| `189.json` | IGP-M (189) | [API SGS](https://api.bcb.gov.br/dados/serie/bcdata.sgs.189/dados?formato=json&dataInicial=01/01/2025&dataFinal=01/11/2026) | 22 | `3eb862feaef9c96d4457c8605fe22db56fdbd6becf302d71476031c1c4f69e6f` |

O `OfflineIndicatorValuesSeeder` lê esses arquivos e envia o conteúdo ao mesmo
validador e mecanismo de persistência utilizados pela sincronização online.
