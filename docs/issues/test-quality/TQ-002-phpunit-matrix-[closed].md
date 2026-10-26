# TQ-002 — Matriz PHPUnit determinística

**Status:** closed
**Epic:** test-quality
**Branch directive:** development (explicit operator instruction)

## Scope

Suítes/default explícitas, grupos, descoberta sem duplicação e warnings/depreciações/risky/incomplete fatais.

## Evidence

- Implementação e documentação versionáveis concluídas.
- Gate local `default`: 93 testes, 610 asserções, 0 skips.
- PHPStan e PSR-12 verdes; audit sem advisory; cobertura medida em 5,68% de linhas.
- `docker compose --profile db|http|full config --quiet` validado sem iniciar serviços.

## Runtime acceptance

- Unit: 68 testes, 276 asserções, 0 skips.
- Contract: 25 testes, 334 asserções, 0 skips.
- PostgreSQL: 10 testes, 60 asserções, 0 skips.
- MySQL: 6 testes, 46 asserções, 0 skips.
- Integração agregada DB: 16 testes, 106 asserções, 0 skips.
- HTTP/2 real: 6 testes, 28 asserções, TLS verificado e versão negociada `2`.
- Quatro serviços `y2-test` permaneceram ativos e saudáveis; nenhum volume foi removido.
