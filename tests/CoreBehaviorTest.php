<?php

declare(strict_types=1);

namespace Tests;

use Exception;
use InvalidArgumentException;
use JsonException;
use ReflectionProperty;
use RuntimeException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use YeAPF\Request;
use YeAPF\Result;
use YeAPF\YeAPFConfig;
use YeAPF\YeAPFException;

#[Group('unit')]
final class CoreBehaviorTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $previousServer = [];
    /** @var array<string, mixed> */
    private array $previousGet = [];
    /** @var array<string, mixed> */
    private array $previousConfig = [];
    private string $temporaryRoot = '';

    protected function setUp(): void
    {
        $this->previousServer = $_SERVER;
        $this->previousGet = $_GET;
        $property = new ReflectionProperty(YeAPFConfig::class, 'configAreas');
        $value = $property->getValue();
        $this->previousConfig = is_array($value) ? $value : [];
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->previousServer;
        $_GET = $this->previousGet;
        (new ReflectionProperty(YeAPFConfig::class, 'configAreas'))->setValue(null, $this->previousConfig);
        if ($this->temporaryRoot !== '') {
            $this->removeDirectory($this->temporaryRoot);
        }
    }

    public function testFunctionFirstCoreLoadsWithoutContainerOrConnection(): void
    {
        $this->assertTrue(class_exists(Request::class));
        $this->assertSame([], Request::jsonBody(''));
        $this->assertSame(204, (new Result(204))['return']);
    }

    public function testRequestReadsQueryHeadersAndJsonFromInjectedInputs(): void
    {
        $request = Request::fromGlobals(
            ['page' => '2'],
            ['HTTP_X_REQUEST_ID' => 'req-1', 'CONTENT_TYPE' => 'application/json'],
            '{"name":"Ana"}'
        );

        $this->assertSame(['page' => '2'], $request['query']);
        $this->assertSame('req-1', $request['headers']['x-request-id']);
        $this->assertSame('application/json', $request['headers']['content-type']);
        $this->assertSame(['name' => 'Ana'], $request['json']);
        $this->assertSame('fallback', Request::query('missing', 'fallback', []));
        $this->assertSame('abc', Request::header('X-Token', null, ['HTTP_X_TOKEN' => 'abc']));
    }

    public function testInvalidJsonRetainsJsonExceptionAsCause(): void
    {
        try {
            Request::jsonBody('{invalid');
            $this->fail('Invalid JSON must throw.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('Invalid JSON request body.', $exception->getMessage());
            $this->assertInstanceOf(JsonException::class, $exception->getPrevious());
        }
    }

    public function testConfigurationSupportsDefaultsAndOverridesWithoutPersistentStateLeak(): void
    {
        $property = new ReflectionProperty(YeAPFConfig::class, 'configAreas');
        $property->setValue(null, ['feature' => (object) ['enabled' => true]]);

        $this->assertTrue(YeAPFConfig::getSection('feature', 'enabled'));
        $this->assertNull(YeAPFConfig::getSection('missing'));
        $this->assertTrue(YeAPFConfig::allowExpressionsInSanitizedInput(false) === false);
        $this->assertFalse(YeAPFConfig::allowExpressionsInSanitizedInput());
        YeAPFConfig::allowExpressionsInSanitizedInput(true);
    }

    public function testMalformedConfigurationFailsWithFileContext(): void
    {
        $this->temporaryRoot = sys_get_temp_dir() . '/y2-config-' . bin2hex(random_bytes(4));
        mkdir($this->temporaryRoot . '/.config', 0775, true);
        file_put_contents($this->temporaryRoot . '/.config/broken.json', '{broken');
        $_SERVER['SCRIPT_FILENAME'] = $this->temporaryRoot . '/index.php';
        (new ReflectionProperty(YeAPFConfig::class, 'configAreas'))->setValue(null, []);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('broken.json cannot be parsed');
        YeAPFConfig::open();
    }

    public function testExceptionPreservesCauseAndResultSerializationIsIsolated(): void
    {
        $cause = new RuntimeException('root cause', 17);
        $exception = new YeAPFException('public failure', 42, $cause);
        $first = new Result(422);
        $first['error'] = 'invalid';
        $first['token'] = 'must-not-leak';
        $first['file'] = '/private/application/source.php';
        $second = new Result();

        $this->assertSame($cause, $exception->getPrevious());
        $this->assertSame(42, $exception->getCode());
        $this->assertSame(
            ['return' => 422, 'error' => 'invalid', 'token' => '[REDACTED]', 'file' => 'source.php'],
            $first->jsonSerialize()
        );
        $this->assertStringNotContainsString('must-not-leak', json_encode($first, JSON_THROW_ON_ERROR));
        $this->assertStringNotContainsString('/private/application', json_encode($first, JSON_THROW_ON_ERROR));
        $this->assertSame(['return' => 200], $second->jsonSerialize());
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }
        foreach (scandir($directory) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $directory . '/' . $item;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($directory);
    }
}
