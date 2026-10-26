<?php

declare(strict_types=1);

namespace YeAPF;

use InvalidArgumentException;
use RuntimeException;

class Request
{
    public static function query(string $key, mixed $default = null, ?array $query = null): mixed
    {
        $query ??= $_GET;
        return array_key_exists($key, $query) ? $query[$key] : $default;
    }

    public static function header(string $name, mixed $default = null, ?array $server = null): mixed
    {
        $server ??= $_SERVER;
        $normalized = strtoupper(str_replace('-', '_', trim($name)));
        $keys = [$normalized, 'HTTP_' . $normalized];
        if ($normalized === 'CONTENT_TYPE' || $normalized === 'CONTENT_LENGTH') {
            $keys = [$normalized, 'HTTP_' . $normalized];
        }

        foreach ($keys as $key) {
            if (array_key_exists($key, $server)) {
                return $server[$key];
            }
        }

        return $default;
    }

    /** @return array<string, mixed> */
    public static function jsonBody(?string $body = null): array
    {
        if ($body === null) {
            $body = file_get_contents('php://input');
            if ($body === false) {
                throw new RuntimeException('Unable to read the request body.');
            }
        }

        if (trim($body) === '') {
            return [];
        }

        try {
            $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new InvalidArgumentException('Invalid JSON request body.', 0, $exception);
        }

        if (!is_array($decoded)) {
            throw new InvalidArgumentException('JSON request body must decode to an object or array.');
        }

        return $decoded;
    }

    /**
     * @param array<string, mixed>|null $query
     * @param array<string, mixed>|null $server
     * @return array{query:array<string,mixed>,headers:array<string,mixed>,json:array<string,mixed>}
     */
    public static function fromGlobals(?array $query = null, ?array $server = null, ?string $body = null): array
    {
        $query ??= $_GET;
        $server ??= $_SERVER;
        $headers = [];
        foreach ($server as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $header = strtolower(str_replace('_', '-', substr($key, 5)));
                $headers[$header] = $value;
            } elseif ($key === 'CONTENT_TYPE' || $key === 'CONTENT_LENGTH') {
                $headers[strtolower(str_replace('_', '-', $key))] = $value;
            }
        }

        return ['query' => $query, 'headers' => $headers, 'json' => self::jsonBody($body)];
    }

    /**
     * @param array<string, mixed> $data
     * @param array{username?:string,password?:string} $user
     * @param list<string> $headers
     */
    public static function do(
        string $url,
        string $method,
        array $data = [],
        array $user = [],
        array $headers = ['Content-Type: application/json']
    ): string {
        $ch = curl_init();
        if ($ch === false) {
            throw new RuntimeException('Unable to initialize cURL.');
        }

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_POSTFIELDS => json_encode($data, JSON_THROW_ON_ERROR),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
        ]);

        if ($user !== []) {
            curl_setopt($ch, CURLOPT_USERPWD, ($user['username'] ?? '') . ':' . ($user['password'] ?? ''));
        }

        $response = curl_exec($ch);
        if ($response === false) {
            $message = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException('HTTP request failed: ' . $message);
        }

        curl_close($ch);
        return $response;
    }
}
