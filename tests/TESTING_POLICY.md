# Testing Policy

## Objetivo

Manter testes determinísticos, legíveis e seguros para adoção modular do Y2.

## Isolamento obrigatório

- `unit` e `contract` não podem abrir sockets, ler `.env`, iniciar Docker, usar hora real sem relógio controlável, executar processos externos ou deixar estado global persistente.
- Toda alteração de superglobal, configuração estática, registry, relógio ou fixture deve ser restaurada em `tearDown`/`finally`.
- Integrações usam apenas o `docker-compose.yml` e `.env.example` da raiz.
- Nenhum teste pode consumir credencial, rota ou dado de uma aplicação externa.
- Docker é opt-in; runners locais nunca removem volumes e não executam `down -v`.

## Padrão de escrita

- `declare(strict_types=1);` em arquivos PHP novos.
- Classe `final` quando extensão não for intencional.
- Métodos de teste retornam `: void`.
- Fixtures pequenas, locais e determinísticas.
- Uma intenção comportamental observável por método.
- Preferir `assertSame()` e APIs públicas; busca textual é somente sentinela complementar.
- Testes de integração usam tabelas com prefixo `yeapf2_`.

## Gates

Toda mudança PHP deve passar:

1. lint;
2. `unit` e `contract`;
3. PHPStan com baseline versionada e não crescente;
4. PHPCS/PSR-12 no escopo migrado;
5. cobertura medida com PCOV;
6. PostgreSQL + MySQL para mudanças de banco;
7. HTTP/2 real para mudanças no gateway/contrato de rede.

Warnings, depreciações, testes incompletos e arriscados falham a execução. Um skip de infraestrutura deve ser reportado como não executado, nunca como suíte verde.

## Segurança de artefatos

Não versionar credenciais, logs, caches, `.env`, cobertura ou `.test-results/`. Falhas podem registrar status, versão HTTP, headers não sensíveis e corpo artificial; tokens, cookies, senhas e authorization headers devem ser mascarados.
