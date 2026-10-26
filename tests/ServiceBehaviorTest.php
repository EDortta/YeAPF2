<?php

declare(strict_types=1);

namespace Tests;

use ReflectionMethod;
use ReflectionProperty;
use RuntimeException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use YeAPF\IBulletin;
use YeAPF\Services\HTTP2Service;
use YeAPF\Services\SSEEvent;
use YeAPF\WebApp;

#[Group('unit')]
final class ServiceBehaviorTest extends TestCase
{
    /** @var array<string, array<string, mixed>> */
    private array $previousHandlers = [];

    protected function setUp(): void
    {
        $property = new ReflectionProperty(WebApp::class, 'handlers');
        $value = $property->getValue();
        $this->previousHandlers = is_array($value) ? $value : [];
        $property->setValue(null, [
            'GET' => [], 'POST' => [], 'DELETE' => [], 'PATCH' => [], 'HEAD' => [], 'OPTIONS' => [],
        ]);
    }

    protected function tearDown(): void
    {
        (new ReflectionProperty(WebApp::class, 'handlers'))->setValue(null, $this->previousHandlers);
        WebApp::setURI(null);
    }

    public function testHttp2PathParsingPreservesSegmentsAndSeparatesQuery(): void
    {
        $service = $this->service();

        $this->assertSame(['users', '42'], $service->getPathFromURI('/users/42?expand=roles'));
        $this->assertSame(
            ['resource' => 'users', 'id' => '42'],
            $service->getPathFromURI('/users/42?expand=roles', 'resource', 'id')
        );
    }

    public function testHttp2RouteRegistrationResolvesMethodAndParametersWithoutServer(): void
    {
        $service = $this->service();
        $handler = static fn (string $id): string => $id;
        $service->setHandler('/users/{{id}}', $handler, ['get']);
        $method = new ReflectionMethod(HTTP2Service::class, 'findHandler');

        $definition = $method->invoke($service, 'GET', '/users/abc?trace=1');

        $this->assertIsArray($definition);
        $this->assertSame($handler, $definition['attendant']);
        $this->assertSame(['{{id}}' => 'id'], $definition['inlineParams']);
        $this->assertNull($method->invoke($service, 'POST', '/users/abc'));
    }

    public function testHttp2ApiDetailsMergeWithoutCrossSectionLeak(): void
    {
        $service = $this->service();
        $service->setAPIDetail('info', 'contact', ['name' => 'A']);
        $service->setAPIDetail('info', 'contact', ['url' => 'https://example.test']);
        $service->setAPIDetail('tags', 'public', 'Public');

        $this->assertSame(
            ['name' => 'A', 'url' => 'https://example.test'],
            $service->getAPIDetail('info', 'contact')
        );
        $this->assertSame('Public', $service->getAPIDetail('tags', 'public'));
        $this->assertNull($service->getAPIDetail('info', 'missing'));
    }

    public function testWebAppRoutesAreMethodSpecificAndUnknownMethodReturnsNull(): void
    {
        $handler = static fn (): int => 204;
        WebApp::setRouteHandler('/health', 'GET', $handler);

        $this->assertSame($handler, WebApp::getRouteHandlerDefinition('/health', 'get')['handler']);
        $this->assertNull(WebApp::getRouteHandlerDefinition('/health', 'POST'));
        $this->assertNull(WebApp::getRouteHandlerDefinition('/health', 'TRACE'));
        $this->assertNull(WebApp::getRouteHandlerDefinition('/missing', 'GET'));
    }

    public function testHttp2MiddlewareRunsInRegistrationOrderAroundHandler(): void
    {
        $service = $this->service();
        $order = [];
        $service->useMiddleware(static function (array $request, callable $next) use (&$order): mixed {
            $order[] = 'first-before';
            $response = $next($request);
            $order[] = 'first-after';
            return $response;
        });
        $service->useMiddleware(static function (array $request, callable $next) use (&$order): mixed {
            $order[] = 'second-before';
            $response = $next($request);
            $order[] = 'second-after';
            return $response;
        });

        $result = $service->runMiddleware(['uri' => '/test'], static function () use (&$order): int {
            $order[] = 'handler';
            return 204;
        });

        $this->assertSame(204, $result);
        $this->assertSame(
            ['first-before', 'second-before', 'handler', 'second-after', 'first-after'],
            $order
        );
    }

    public function testHttp2MiddlewareCanShortCircuitHandler(): void
    {
        $service = $this->service();
        $handlerCalled = false;
        $service->useMiddleware(static fn (array $request, callable $next): int => 202);

        $result = $service->runMiddleware([], static function () use (&$handlerCalled): int {
            $handlerCalled = true;
            return 200;
        });

        $this->assertSame(202, $result);
        $this->assertFalse($handlerCalled);
    }

    public function testHttp2MiddlewareExceptionPropagatesToResponseBoundary(): void
    {
        $service = $this->service();
        $service->useMiddleware(static function (): never {
            throw new RuntimeException('controlled middleware failure');
        });

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('controlled middleware failure');
        $service->runMiddleware([], static fn (): int => 200);
    }

    public function testSseEventSerializesFiniteProtocolFrameWithoutEcho(): void
    {
        ob_start();
        $frame = (string) new SSEEvent('update', ['ok' => true], 'event-1', 1500);
        $output = (string) ob_get_clean();

        $this->assertSame('', $output);
        $this->assertSame(
            "id: event-1\nevent: update\nretry: 1500\ndata: {\"ok\":true}\n\n",
            $frame
        );
    }

    private function service(): HTTP2Service
    {
        return new class extends HTTP2Service {
            public function answerQuery(IBulletin &$bulletin, string $uri): void
            {
            }

            public function startup(): void
            {
            }

            public function shutdown(): void
            {
            }
        };
    }
}
