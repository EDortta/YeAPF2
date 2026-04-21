# DB Test Environments (Docker)

This folder provides optional real-engine containers for contract/integration tests.

## Start containers

```bash
cd tests/db-environments
docker compose up -d
```

## Stop containers

```bash
cd tests/db-environments
docker compose down
```

## Connection endpoints

- PostgreSQL: `127.0.0.1:55432` db=`yeapf2_test` user=`yeapf2` pass=`yeapf2`
- MySQL: `127.0.0.1:53306` db=`yeapf2_test` user=`yeapf2` pass=`yeapf2`

## Contract harness now

Current shared contract suite runs with a stub bootstrap (`tests/DriverContractHarnessStubTest.php`) to guarantee engine-agnostic behavior at unit-test level.

When PostgreSQL/MySQL driver bootstraps are added, wire them under `tests/contracts/bootstrap/` implementing `Tests\Contracts\DriverContractBootstrapInterface` and run the same abstract suite against each engine.

## ORM step-by-step flow test (Docker PostgreSQL)

This repository now includes a real integration test that demonstrates YeAPF2 ORM usage step-by-step against PostgreSQL in Docker:

- `tests/ORMDockerPostgresFlowTest.php`

It covers:
- model definition
- persistent collection creation (table/columns provisioning)
- insert
- get by id
- update
- find by example
- list documents
- delete

Run it with:

```bash
tests/db-environments/run-orm-flow-test.sh
```
