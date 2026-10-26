<?php

declare(strict_types=1);

namespace Tests\IntegrationHttp2;

use RuntimeException;

final class Http2TestClient
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $caFile
    ) {
    }

    /**
     * @param list<string> $headers
     * @param bool $stream Consume response chunks through a write callback (required for SSE).
     * @return array{status:int,body:string,headers:array<string,list<string>>,http_version:int,content_type:?string}
     */
    public function request(
        string $method,
        string $path,
        ?string $body = null,
        array $headers = [],
        bool $stream = false
    ): array {
        $handle = curl_init($this->baseUrl . $path);
        if ($handle === false) {
            throw new RuntimeException('Unable to initialize cURL.');
        }

        $responseHeaders = [];
        $responseBody = '';
        curl_setopt_array($handle, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => !$stream,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2TLS,
            CURLOPT_CAINFO => $this->caFile,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_HEADERFUNCTION => static function ($curl, string $line) use (&$responseHeaders): int {
                if (str_contains($line, ':')) {
                    [$name, $value] = explode(':', $line, 2);
                    $responseHeaders[strtolower(trim($name))][] = trim($value);
                }
                return strlen($line);
            },
        ]);
        if ($body !== null) {
            curl_setopt($handle, CURLOPT_POSTFIELDS, $body);
        }
        if ($stream) {
            curl_setopt(
                $handle,
                CURLOPT_WRITEFUNCTION,
                static function ($curl, string $chunk) use (&$responseBody): int {
                    $responseBody .= $chunk;
                    return strlen($chunk);
                }
            );
        }

        $response = curl_exec($handle);
        if ($response === false) {
            $error = curl_error($handle);
            $number = curl_errno($handle);
            throw new RuntimeException("cURL error $number: $error");
        }

        $result = [
            'status' => (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE),
            'body' => $stream ? $responseBody : (string) $response,
            'headers' => $responseHeaders,
            'http_version' => (int) curl_getinfo($handle, CURLINFO_HTTP_VERSION),
            'content_type' => curl_getinfo($handle, CURLINFO_CONTENT_TYPE) ?: null,
        ];
        return $result;
    }
}
