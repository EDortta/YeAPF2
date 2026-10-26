<?php

declare(strict_types=1);

/**
 * Artificial front controller used only by the Y2 HTTP integration profile.
 *
 * @param array<string, mixed> $query
 * @param array<string, string> $headers
 */
function dispatchTestRequest(string $method, string $path, array $query, string $body, array $headers): never
{
    header('X-Middleware-Order: request-id,router,response');
    header('X-Request-Isolated: ' . (headerValue($headers, 'X-Isolation-Id') ?? 'none'));

    if ($path === '/__health' && $method === 'GET') {
        jsonResponse(200, ['status' => 'ok', 'runtime' => 'php-fpm']);
    }

    if (preg_match('#^/v1/items/([^/]+)$#', $path, $matches) === 1) {
        if ($method !== 'GET') {
            header('Allow: GET');
            jsonResponse(405, ['error' => 'method_not_allowed']);
        }
        jsonResponse(200, ['id' => rawurldecode($matches[1]), 'query' => $query]);
    }

    if ($path === '/v1/echo') {
        if ($method !== 'POST') {
            header('Allow: POST');
            jsonResponse(405, ['error' => 'method_not_allowed']);
        }
        if ($body === '') {
            jsonResponse(200, ['body' => null, 'contentType' => headerValue($headers, 'Content-Type')]);
        }
        try {
            $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            jsonResponse(400, ['error' => 'invalid_json']);
        }
        jsonResponse(200, [
            'body' => $decoded,
            'contentType' => headerValue($headers, 'Content-Type'),
            'requestHeader' => headerValue($headers, 'X-Test-Header'),
        ]);
    }

    if ($path === '/v1/short-circuit') {
        header('X-Middleware-Short-Circuit: true');
        jsonResponse(202, ['handled' => 'middleware']);
    }

    if ($path === '/v1/error') {
        header('X-Error-Type: controlled');
        jsonResponse(500, ['error' => 'controlled_failure']);
    }

    if ($path === '/v1/sse' && $method === 'GET') {
        http_response_code(200);
        header('Content-Type: text/event-stream; charset=utf-8');
        header('Cache-Control: no-cache');
        echo "id: event-1\n";
        echo "event: ready\n";
        echo "retry: 1000\n";
        echo "data: {\"status\":\"ok\"}\n\n";
        echo "event: complete\n";
        echo "data: done\n\n";
        exit;
    }

    jsonResponse(404, ['error' => 'not_found']);
}

/** @param array<string, string> $headers */
function headerValue(array $headers, string $wanted): ?string
{
    foreach ($headers as $name => $value) {
        if (strcasecmp($name, $wanted) === 0) {
            return $value;
        }
    }
    return null;
}

/** @param array<string, mixed> $payload */
function jsonResponse(int $status, array $payload): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    exit;
}
