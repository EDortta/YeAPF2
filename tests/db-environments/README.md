# Infraestrutura de bancos para testes

A única fonte de infraestrutura é o `docker-compose.yml` da raiz, configurado por `.env` derivado de `.env.example`. Não existe Compose sob `tests/db-environments/`.

## Contrato

| Engine | Serviço | Host | Porta | Banco/schema | Usuário |
|---|---|---|---:|---|---|
| PostgreSQL 16 | `postgres` | `127.0.0.1` | 35432 | `yeapf2_test` / `public` | `yeapf2` |
| MySQL 8.4 | `mysql` | `127.0.0.1` | 33306 | `yeapf2_test` | `yeapf2` |

As portas são publicadas somente no loopback. O projeto Compose se chama `y2-test`, não usa `container_name` e mantém volumes exclusivos `postgres-data`/`mysql-data`.

`Tests\Helpers\IntegrationDatabaseConfig` centraliza os endpoints PHP. Overrides opcionais:

- `Y2_TEST_POSTGRES_HOST|PORT|DATABASE|SCHEMA|USER|PASSWORD`;
- `Y2_TEST_MYSQL_HOST|PORT|DATABASE|SCHEMA|USER|PASSWORD`.

Valores vazios ou portas inválidas falham com o nome exato da variável. Os defaults coincidem com `.env.example`.

## Validação e execução opt-in

```bash
cp .env.example .env                  # somente se .env ainda não existir
docker compose --profile db config --quiet
docker compose --profile db up -d --wait postgres mysql
composer --working-dir=tests test:integration-postgres
composer --working-dir=tests test:integration-mysql
docker compose --profile db ps
```

Para um engine isolado, inicie apenas o serviço correspondente. Os wrappers legados delegam ao runner canônico:

```bash
tests/db-environments/run-orm-flow-test.sh
tests/db-environments/run-orm-flow-test-mysql.sh
tests/db-environments/run-orm-flow-tests.sh
```

Eles nunca contêm lógica própria de healthcheck e nunca apagam volumes.

## Escopo dos testes

- `ORMDockerPostgresFlowTest` e `ORMDockerMySQLFlowTest`: CRUD ORM.
- `SchemaSessionBootstrapTest`: bootstrap/idempotência PostgreSQL.
- `PostgreSQLDriverContractTest` e `MySQLDriverContractTest`: mesma matriz de driver, inspector, DDL, transação/rollback e aplicação idempotente.
- Todas as tabelas são artificiais e começam com `yeapf2_`.

Sem serviço disponível, a suíte de integração é pulada com comando operacional explícito. Esse skip não equivale a sucesso.

## Diagnóstico não destrutivo

```bash
docker compose --profile db ps
docker compose --profile db logs --tail=100 postgres mysql
ss -ltn '( sport = :35432 or sport = :33306 )'
```

Não pare containers de outros projetos. Não execute `docker compose down -v`; nenhum script deste repositório remove volumes.
