# Infraestrutura de bancos para testes

> **Fonte de verdade para agentes:** leia este documento antes de procurar bancos
> locais, alterar portas ou investigar a infraestrutura da máquina.

Os testes de integração usam exclusivamente os serviços definidos no
`docker-compose.yml` da **raiz do repositório**. Não é necessário instalar
PostgreSQL ou MySQL diretamente no host.

Todos os comandos deste documento devem ser executados a partir da raiz de Y2.

## Resumo operacional

```bash
# Primeira execução: cria a configuração local, que não deve ser commitada.
cp .env.example .env

# Inicia os dois bancos e espera os healthchecks.
docker compose up -d --wait

# Confirma estado, portas e saúde.
docker compose ps

# Encerra os containers sem apagar os dados.
docker compose down
```

O runner de handoff (`./tests/run-handoff.sh`) também inicia os dois serviços e
aguarda os healthchecks automaticamente.

## Serviços e endpoints fixos

| Serviço | Imagem | Container | Endpoint no host | Endpoint no container | Banco | Usuário | Senha de teste |
|---|---|---|---|---|---|---|---|
| PostgreSQL | `postgres:16` | `yeapf2-test-postgres` | `127.0.0.1:35432` | `5432` | `yeapf2_test` | `yeapf2` | `yeapf2` |
| MySQL | `mysql:8.4` | `yeapf2-test-mysql` | `127.0.0.1:33306` | `3306` | `yeapf2_test` | `yeapf2` | `yeapf2` |

A senha `root` do MySQL no ambiente de teste também é `root`.

As portas `35432` e `33306`, o banco e as credenciais fazem parte do contrato
atual dos testes. Os testes PHP ainda usam esses valores como constantes;
portanto, não altere somente a `.env`. Uma mudança de endpoint deve atualizar,
no mesmo patch:

- `.env.example`;
- a `.env` local;
- `tests/SchemaSessionBootstrapTest.php`;
- `tests/ORMDockerPostgresFlowTest.php`;
- `tests/ORMDockerMySQLFlowTest.php`;
- este documento e `tests/README.testing.md`.

## Arquivos da infraestrutura

| Arquivo | Responsabilidade | Versionado? |
|---|---|---|
| `docker-compose.yml` | Serviços, imagens, portas, volumes e healthchecks | Sim |
| `.env.example` | Valores de referência compatíveis com os testes | Sim |
| `.env` | Configuração local consumida pelo Docker Compose | **Não** |
| `tests/db-environments/README.md` | Esta fonte de verdade operacional | Sim |
| `tests/db-environments/run-orm-flow-test.sh` | Sobe os bancos e testa ORM/PostgreSQL | Sim |
| `tests/db-environments/run-orm-flow-test-mysql.sh` | Sobe os bancos e testa ORM/MySQL | Sim |
| `tests/db-environments/run-orm-flow-tests.sh` | Executa os dois fluxos ORM | Sim |

A `.env` já está coberta pelo `.gitignore`. Nunca force sua inclusão no Git.
Somente `.env.example` deve acompanhar o repositório.

## Healthchecks e persistência

O `docker compose up -d --wait` retorna com sucesso apenas quando:

- PostgreSQL responde a `pg_isready` com o banco e usuário configurados;
- MySQL responde a `mysqladmin ping` com a senha root configurada.

Os dados persistem nos volumes lógicos:

- `postgres-data`;
- `mysql-data`.

Use `docker compose down` para recriar containers preservando os dados. Para
zerar completamente os bancos de teste, use deliberadamente:

```bash
docker compose down -v
docker compose up -d --wait
```

## Testes que dependem dos bancos

| Teste/suíte | Dependência |
|---|---|
| `tests/ORMDockerPostgresFlowTest.php` | PostgreSQL em `127.0.0.1:35432` |
| `tests/SchemaSessionBootstrapTest.php` | PostgreSQL em `127.0.0.1:35432` |
| `tests/ORMDockerMySQLFlowTest.php` | MySQL em `127.0.0.1:33306` |
| `./tests/run-handoff.sh schema-bootstrap` | PostgreSQL; o runner inicia ambos os serviços |
| `./tests/run-handoff.sh` | PostgreSQL e MySQL |

Execução direta dos fluxos ORM:

```bash
tests/db-environments/run-orm-flow-test.sh
tests/db-environments/run-orm-flow-test-mysql.sh

# Ou ambos, em sequência:
tests/db-environments/run-orm-flow-tests.sh
```

O fluxo ORM de cada engine cobre criação de tabela/colunas, inserção, leitura,
atualização, busca por exemplo, listagem e exclusão.

## Diagnóstico rápido para agentes

Não comece procurando uma instalação local do PostgreSQL/MySQL. Execute, nesta
ordem:

```bash
# 1. Confere se a configuração pode ser resolvida.
docker compose config --quiet

# 2. Inicia e aguarda os dois bancos.
docker compose up -d --wait

# 3. Confere saúde e mapeamento das portas.
docker compose ps

# 4. Se algum serviço falhar, lê somente os logs da infraestrutura de teste.
docker compose logs --tail=100 postgres mysql
```

Se uma porta estiver ocupada, identifique o processo antes de qualquer ação:

```bash
ss -ltn '( sport = :35432 or sport = :33306 )'
```

Não pare nem reconfigure containers de outros projetos. Resolva a colisão com o
responsável ou altere todo o contrato listado em **Serviços e endpoints fixos**.

Quando um teste informar que o banco Docker não está acessível, isso significa
que o socket esperado não respondeu. Confirme primeiro `docker compose ps` e o
healthcheck; não trate esses cenários como testes unitários isolados.

## Contratos unitários versus integração

A suíte compartilhada de contratos usa atualmente o bootstrap stub em
`tests/DriverContractHarnessStubTest.php` para validar comportamento
independente de engine. Os três testes listados na matriz acima são integrações
reais e, por definição, precisam dos containers.

Quando bootstraps reais de PostgreSQL/MySQL forem adicionados ao contract
harness, eles devem ficar em `tests/contracts/bootstrap/`, implementar
`Tests\Contracts\DriverContractBootstrapInterface` e reutilizar a mesma suíte
abstrata para ambas as engines.
