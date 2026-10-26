# Testes do Y2

A matriz separa execução local determinística de integrações opt-in. O comando padrão nunca inicia Docker, lê `.env` ou abre socket.

## Instalação

```bash
composer install --working-dir=tests
```

O lock em `tests/composer.lock` é versionado. Credenciais locais e resultados não são versionados.

## Matriz

| Suíte | Conteúdo | Docker/rede |
|---|---|---|
| `default` | agregação de `unit` + `contract` | proibido |
| `unit` | núcleo, configuração, segurança, logger, i18n, HTTP/WebApp/SSE com doubles | proibido |
| `contract` | interfaces, sintetizadores e harness stub | proibido |
| `integration-postgres` | ORM, bootstrap e contract harness PostgreSQL real | opt-in |
| `integration-mysql` | ORM e contract harness MySQL real | opt-in |
| `integration` | PostgreSQL + MySQL | opt-in |
| `integration-http2` | TLS/ALPN/Caddy/PHP-FPM/cURL HTTP/2 | opt-in |

Todos os testes possuem grupos equivalentes (`unit`, `contract`, `integration`, `postgres`, `mysql`, `http2`). `phpunit.xml` usa `default` quando nenhuma suíte é indicada e falha por warning, depreciação, teste incompleto ou arriscado.

## Comandos sem Docker

```bash
composer --working-dir=tests test:unit
composer --working-dir=tests test:contract
composer --working-dir=tests test             # default = unit + contract
composer --working-dir=tests lint
composer --working-dir=tests analyse
composer --working-dir=tests style
composer --working-dir=tests test:coverage
```

Cobertura gera `.test-results/coverage/clover.xml` e `summary.txt`. A primeira medição válida, em 2026-10-26 com PHP 8.4/PCOV, foi 5,77% de linhas (1.085/18.810); é uma medição inicial, não uma meta inventada. Mudanças devem preservar ou elevar a cobertura dos arquivos tocados.

## Integrações de banco (opt-in)

Copie `.env.example` para `.env` uma vez. A raiz é a única fonte de Compose:

```bash
docker compose --profile db config --quiet
docker compose --profile db up -d --wait postgres mysql
composer --working-dir=tests test:integration-postgres
composer --working-dir=tests test:integration-mysql
docker compose --profile db ps
```

O helper `Tests\Helpers\IntegrationDatabaseConfig` usa defaults idênticos ao Compose e aceita `Y2_TEST_POSTGRES_*`/`Y2_TEST_MYSQL_*`. Os testes usam somente tabelas `yeapf2_*`.

## Integração HTTP/2 (opt-in)

Consulte [`http-environment/README.md`](http-environment/README.md). Resumo:

```bash
docker compose --profile http config --quiet
docker compose --profile http up -d --wait php-http caddy-http
docker compose --profile http run --rm http-client
docker compose --profile http ps
```

O tráfego automatizado parte apenas de `http-client`, valida a CA interna do Caddy e exige `CURL_HTTP_VERSION_2_0`; `--insecure` é proibido.

## Runner e artefatos

```bash
./tests/run-handoff.sh unit
./tests/run-handoff.sh contract
./tests/run-handoff.sh integration-postgres  # inicia somente postgres
./tests/run-handoff.sh integration-mysql     # inicia somente mysql
./tests/run-handoff.sh integration           # inicia ambos
./tests/run-handoff.sh integration-http2
```

`unit`, `contract` e `default` não executam nenhum comando Docker. Integrações validam o Compose da raiz e iniciam apenas os serviços solicitados. O runner nunca usa `down -v` e deixa serviços ativos por padrão.

Artefatos duráveis ficam em `.test-results/<suite>/<timestamp>/`: `meta.txt`, `console.txt`, `junit.xml`, `testdox.txt`, `exit-code.txt`, `summary.txt` e, nas integrações, `docker-compose-ps.txt`. HTTP/2 também registra versões de PHP/cURL e a versão HTTP negociada.
