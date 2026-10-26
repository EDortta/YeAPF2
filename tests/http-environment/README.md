# Ambiente de integração HTTP/2

Ambiente artificial e isolado para validar o contrato recebido por um consumidor depois de TLS, ALPN, Caddy e PHP-FPM. Nenhuma rota, credencial ou configuração de aplicação consumidora é usada.

## Serviços e confiança TLS

- `php-http`: PHP-FPM com `app/index.php` e rotas determinísticas.
- `caddy-http`: único gateway, HTTPS em `8443`, certificado da CA interna e proxy FastCGI.
- `http-client`: PHPUnit + libcurl dentro da rede Compose; é a única origem automatizada de tráfego.
- A CA fica no volume `caddy-data`, montado somente para leitura no cliente. Os testes nunca usam `--insecure`.
- Apenas Caddy publica uma porta de diagnóstico: `127.0.0.1:8443`.

## Comandos

```bash
# Não inicia serviços
docker compose --profile http config --quiet

# Requer autorização operacional
docker compose --profile http up -d --wait php-http caddy-http
docker compose --profile http run --rm http-client

# Diagnóstico não mutante
docker compose --profile http ps
docker compose --profile http logs --tail=100 caddy-http php-http
```

O wrapper `./tests/run-handoff.sh integration-http2` grava PHP, cURL, PHPUnit, JUnit, TestDox, versão HTTP negociada e estado do Compose em `.test-results/integration-http2/<timestamp>/`. Ele não remove containers nem volumes.

## Endpoints artificiais

| Endpoint | Contrato |
|---|---|
| `GET /__health` | atravessa Caddy e PHP-FPM |
| `GET /v1/items/{id}` | parâmetro e query; demais métodos retornam 405 |
| `POST /v1/echo` | JSON válido, inválido e vazio |
| `GET /v1/short-circuit` | resposta curta de middleware |
| `GET /v1/error` | erro controlado 500 |
| `GET /v1/sse` | dois eventos finitos e encerramento limpo |
