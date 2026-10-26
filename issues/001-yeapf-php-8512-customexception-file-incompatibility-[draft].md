# 001 — Corrigir incompatibilidade de `CustomException::$file` do YeAPF com PHP 8.5.12

- status: draft
- data: 2026-10-26
- componente: YeAPF legado em `nossoconsultorio.com.br`
- prioridade: alta — indisponibilidade pública

## Objetivo

Restaurar o funcionamento de `https://nossoconsultorio.com.br/` sob PHP 8.5.12,
eliminando a incompatibilidade da classe `CustomException` com a propriedade
herdada `Exception::$file`.

## Contexto

Após a retirada autorizada da página estática de segurança em 2026-10-26, a raiz
de Nosso Consultório voltou a responder HTTP 500. A resposta pública confirma
`X-Powered-By: PHP/8.5.12`.

O log do aplicativo registra, no momento das requisições:

```
PHP Fatal error: Type of CustomException::$file must be string (as in class Exception)
em YeAPF/includes/yeapf.exceptions.php, linha 25
```

O `index.php` passa na verificação de sintaxe (`php -l`), mas o carregamento do
YeAPF falha antes de a aplicação responder. A falha já existia na versão PHP
anterior e se tornou bloqueante com o runtime web atualizado.

## Escopo

- Inspecionar `YeAPF/includes/yeapf.exceptions.php` e os usos de
  `CustomException` neste aplicativo.
- Corrigir a declaração/uso da propriedade conflitante sem alterar a semântica
  dos metadados de exceção ou expor caminhos internos ao cliente.
- Validar o bootstrap do YeAPF e os fluxos públicos essenciais de Nosso
  Consultório com PHP 8.5.12.
- Manter uma cópia reversível dos arquivos alterados no host antes da ativação.

## Fora de escopo

- Atualizar globalmente o YeAPF em outros domínios sem auditoria individual.
- Alterar Authfy ou EasyPark, que permanecem em página estática de acesso
  restrito.
- Mudar versão de PHP, configurações globais do cPanel ou políticas de exibição
  de erros.

## ARO — Assunções, riscos e questões em aberto

- **Assunção:** a incompatibilidade está limitada ao YeAPF instalado dentro de
  Nosso Consultório; isso deve ser comprovado antes de reutilizar a correção.
- **Risco:** renomear/remover a propriedade pode afetar código que a consome;
  todos os usos devem ser localizados e cobertos por teste de comportamento.
- **Risco de segurança:** detalhes de exceções, caminhos e stack traces não
  podem ser enviados na resposta HTTP pública.
- **Questão em aberto:** identificar se a implementação deve delegar ao campo
  nativo da exceção ou guardar o dado em propriedade própria, compatível com
  PHP 8.5.12.

## Plano de teste

1. Executar `php -l` nos arquivos YeAPF modificados.
2. Criar/rodar teste focado na construção e leitura de `CustomException`.
3. Exercitar o bootstrap local ou isolado com PHP 8.5.12, sem dados reais de
   usuários.
4. Após autorização para ativação, validar externamente a raiz pública e fluxos
   essenciais, confirmando HTTP 200 e ausência do fatal no log novo.
5. Confirmar que respostas de erro não revelam stack trace nem caminhos locais.

## Definição de pronto

- A causa do HTTP 500 está corrigida e documentada.
- `CustomException` funciona sob PHP 8.5.12 sem conflito com `Exception`.
- `https://nossoconsultorio.com.br/` responde HTTP 200 após ativação autorizada.
- Não há novo fatal correspondente no log após os testes.
- Existe procedimento de reversão para os arquivos modificados.
