<?php

declare(strict_types=1);

namespace Tests\IntegrationHttp2;

use RuntimeException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Tests\IntegrationHttp2\Http2TestClient;

#[Group('integration')]
#[Group('http2')]
final class HTTP2IntegrationTest extends TestCase
{
    private Http2TestClient $client;

    protected function setUp(): void
    {
        $this->client = new Http2TestClient(
            getenv('Y2_HTTP_BASE_URL') ?: 'https://caddy-http:8443',
            getenv('Y2_HTTP_CA_FILE') ?: '/caddy-data/caddy/pki/authorities/local/root.crt'
        );
    }

    public function testTrustedTlsNegotiatesHttp2AndReachesPhpHealthController(): void
    {
        $response = $this->client->request('GET', '/__health');

        $this->assertHttp2($response);
        $this->assertSame(200, $response['status']);
        $this->assertSame(['status' => 'ok', 'runtime' => 'php-fpm'], json_decode($response['body'], true));
    }

    public function testUntrustedInternalCertificateIsRejected(): void
    {
        $client = new Http2TestClient('https://caddy-http:8443', '/etc/ssl/certs/ca-certificates.crt');
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/cURL error (60|77):/');
        $client->request('GET', '/__health');
    }

    public function testRouteParametersQueryNotFoundAndWrongMethod(): void
    {
        $found = $this->client->request('GET', '/v1/items/a%20b?expand=roles&limit=2');
        $missing = $this->client->request('GET', '/v1/missing');
        $wrongMethod = $this->client->request('POST', '/v1/items/1');

        $this->assertHttp2($found);
        $this->assertSame(
            ['id' => 'a b', 'query' => ['expand' => 'roles', 'limit' => '2']],
            json_decode($found['body'], true)
        );
        $this->assertSame(404, $missing['status']);
        $this->assertSame(405, $wrongMethod['status']);
        $this->assertSame(['GET'], $wrongMethod['headers']['allow']);
    }

    public function testJsonValidInvalidAndEmptyBodies(): void
    {
        $fixture = __DIR__ . '/../http-environment/fixtures/requests/';
        $headers = ['Content-Type: application/json', 'X-Test-Header: present'];
        $valid = $this->client->request('POST', '/v1/echo', file_get_contents($fixture . 'valid.json'), $headers);
        $invalid = $this->client->request('POST', '/v1/echo', file_get_contents($fixture . 'invalid.json'), $headers);
        $empty = $this->client->request('POST', '/v1/echo', file_get_contents($fixture . 'empty.json'), $headers);

        $this->assertSame(200, $valid['status']);
        $this->assertSame('present', json_decode($valid['body'], true)['requestHeader']);
        $this->assertSame(400, $invalid['status']);
        $this->assertSame(['error' => 'invalid_json'], json_decode($invalid['body'], true));
        $this->assertSame(200, $empty['status']);
        $this->assertNull(json_decode($empty['body'], true)['body']);
    }

    public function testMiddlewareOrderShortCircuitErrorAndRequestIsolation(): void
    {
        $first = $this->client->request('GET', '/v1/short-circuit', headers: ['X-Isolation-Id: first']);
        $second = $this->client->request('GET', '/v1/error');

        $this->assertSame(['request-id,router,response'], $first['headers']['x-middleware-order']);
        $this->assertSame(['true'], $first['headers']['x-middleware-short-circuit']);
        $this->assertSame(202, $first['status']);
        $this->assertSame(500, $second['status']);
        $this->assertSame(['controlled'], $second['headers']['x-error-type']);
        $this->assertSame(['first'], $first['headers']['x-request-isolated']);
        $this->assertSame(['none'], $second['headers']['x-request-isolated']);
    }

    public function testSseReturnsFiniteUnbufferedEventStream(): void
    {
        $response = $this->client->request('GET', '/v1/sse', stream: true);

        $this->assertHttp2($response);
        $this->assertSame(200, $response['status']);
        $this->assertStringStartsWith('text/event-stream', (string) $response['content_type']);
        $this->assertStringContainsString("event: ready\nretry: 1000\n", $response['body']);
        $this->assertStringEndsWith("event: complete\ndata: done\n\n", $response['body']);
    }

    /** @param array{status:int,body:string,headers:array<string,list<string>>,http_version:int,content_type:?string} $response */
    private function assertHttp2(array $response): void
    {
        $this->assertSame(
            CURL_HTTP_VERSION_2_0,
            $response['http_version'],
            sprintf('Expected HTTP/2; status=%d body=%s', $response['status'], $response['body'])
        );
    }
}
