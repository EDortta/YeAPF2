# Y2 — testes e handoff para agentes

Este diretório contém a suíte PHPUnit do framework. A infraestrutura Docker de PostgreSQL/MySQL está documentada em [`db-environments/README.md`](db-environments/README.md); agentes devem consultá-la antes de investigar dependências locais.

O contrato completo entre **IA de testes** e **IA programadora** está no monorepo ERP-DryWall:

`~/Sync/Projects/YouBR/ERP-DryWall/docs/agents/test-runner-handoff.md` (contrato canônico test runner ↔ programador).

## Pré-requisitos

```bash
(cd tests && composer install)
cp .env.example .env                 # somente na primeira execução
docker compose up -d --wait          # PostgreSQL 35432; MySQL 33306
```

## Comandos

| Comando | Uso |
|---------|-----|
| `./tests/run-handoff.sh` | Suíte completa `yeapf2` + artefatos em `.test-results/latest/` |
| `./tests/run-handoff.sh schema-bootstrap` | Só `SchemaSessionBootstrapTest` |
| `php -d opcache.enable_cli=0 tests/vendor/bin/phpunit -c tests/phpunit.xml` | PHPUnit direto (sem artefatos de handoff) |

## Artefatos (não commitar)

Diretório padrão: `Y2/.test-results/latest/`

- `summary.txt` — PASS/FAIL em uma linha
- `exit-code.txt` — código de saída do PHPUnit
- `junit.xml` — falhas estruturadas
- `testdox.txt` — nomes legíveis dos testes
- `console.txt` — stdout/stderr completo
- `meta.txt` — repo, suite, timestamps

## Suítes (`phpunit.xml`)

- **yeapf2** — todos os `*Test.php` neste diretório (exclui `vendor/`, `helpers/`, `fixtures/`)
- **schema-bootstrap** — bootstrap de schema / trava distribuída
