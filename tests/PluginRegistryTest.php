<?php declare(strict_types=1);

require_once __DIR__ . '/../src/yeapf-core.php';

use PHPUnit\Framework\TestCase;

final class PluginRegistryTest extends TestCase
{
    protected function setUp(): void
    {
        $this->resetRegistry();
    }

    public function testFreezeAppliesTypeDefinitionsAndIsIdempotent(): void
    {
        $typeName = 'X_CUSTOM_TYPE';
        $this->assertNull(\YeAPF\BasicTypes::get($typeName));

        \YeAPF\Plugins\Registry::registerTypeProvider(new FixtureTypeProvider());

        \YeAPF\Plugins\Registry::freeze();
        $first = \YeAPF\BasicTypes::get($typeName);

        \YeAPF\Plugins\Registry::freeze();
        $second = \YeAPF\BasicTypes::get($typeName);

        $this->assertTrue(\YeAPF\Plugins\Registry::isFrozen());
        $this->assertSame($first, $second);
        $this->assertIsArray($first);
        $this->assertSame(YeAPF_TYPE_STRING, $first['type']);
    }

    public function testPostFreezeRegistrationThrowsExpectedExceptionCode(): void
    {
        \YeAPF\Plugins\Registry::freeze();

        $this->expectException(\YeAPF\YeAPFException::class);
        $this->expectExceptionCode(YeAPF_PLUGIN_REGISTRY_FROZEN);

        \YeAPF\Plugins\Registry::registerCacheProvider(new FixtureCacheProvider());
    }

    public function testRoleGettersReturnNullWhenNoPluginWasRegistered(): void
    {
        $this->assertNull(\YeAPF\Plugins\Registry::getDocumentValidator('BR.CNPJ'));
        $this->assertNull(\YeAPF\Plugins\Registry::getCacheProvider());
        $this->assertNull(\YeAPF\Plugins\Registry::getAuthProvider());
        $this->assertNull(\YeAPF\Plugins\Registry::getTranslationProvider());
        $this->assertNull(\YeAPF\Plugins\Registry::getLogHandler());
        $this->assertNull(\YeAPF\Plugins\Registry::getDBDriver('fixture'));
    }

    public function testPluginListRegisterPluginAutoWiresRoleSlots(): void
    {
        $validator = new FixtureDocumentValidator();
        $cache = new FixtureCacheProvider();
        $auth = new FixtureAuthProvider();
        $translation = new FixtureTranslationProvider();
        $log = new FixtureLogHandler();
        $driver = new FixtureDBDriver();

        \YeAPF\Plugins\PluginList::registerPlugin($validator, '/tmp/validator.php');
        \YeAPF\Plugins\PluginList::registerPlugin($cache, '/tmp/cache.php');
        \YeAPF\Plugins\PluginList::registerPlugin($auth, '/tmp/auth.php');
        \YeAPF\Plugins\PluginList::registerPlugin($translation, '/tmp/translation.php');
        \YeAPF\Plugins\PluginList::registerPlugin($log, '/tmp/log.php');
        \YeAPF\Plugins\PluginList::registerPlugin($driver, '/tmp/driver.php');

        $this->assertSame($validator, \YeAPF\Plugins\Registry::getDocumentValidator('BR.CNPJ'));
        $this->assertSame($cache, \YeAPF\Plugins\Registry::getCacheProvider());
        $this->assertSame($auth, \YeAPF\Plugins\Registry::getAuthProvider());
        $this->assertSame($translation, \YeAPF\Plugins\Registry::getTranslationProvider());
        $this->assertSame($log, \YeAPF\Plugins\Registry::getLogHandler());
        $this->assertSame($driver, \YeAPF\Plugins\Registry::getDBDriver('fixture'));
    }

    private function resetRegistry(): void
    {
        $registry = new ReflectionClass(\YeAPF\Plugins\Registry::class);

        $properties = [
            'frozen' => false,
            'documentValidators' => [],
            'typeProviders' => [],
            'dbDrivers' => [],
            'cacheProvider' => null,
            'authProvider' => null,
            'translationProvider' => null,
            'logHandler' => null,
        ];

        foreach ($properties as $name => $value) {
            $property = $registry->getProperty($name);
            $property->setAccessible(true);
            $property->setValue(null, $value);
        }

        $pluginList = new ReflectionClass(\YeAPF\Plugins\PluginList::class);
        $pluginProperty = $pluginList->getProperty('plugins');
        $pluginProperty->setAccessible(true);
        $pluginProperty->setValue(null, []);
    }
}

final class FixtureDocumentValidator implements \YeAPF\Plugins\Validator\DocumentValidatorInterface
{
    public function getSupportedKeys(): array
    {
        return ['BR.CNPJ'];
    }

    public function validate(string $key, string $value): bool
    {
        return 'BR.CNPJ' === $key && '' !== trim($value);
    }
}

final class FixtureTypeProvider implements \YeAPF\Plugins\Type\TypeProviderInterface
{
    public function getTypeDefinitions(): array
    {
        return [
            'X_CUSTOM_TYPE' => [
                'type' => YeAPF_TYPE_STRING,
                'length' => 32,
                'required' => true,
            ],
        ];
    }
}

final class FixtureCacheProvider implements \YeAPF\Plugins\Cache\CacheProviderInterface
{
    private array $values = [];

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->values[$key] ?? $default;
    }

    public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
    {
        $this->values[$key] = $value;

        return true;
    }

    public function delete(string $key): bool
    {
        unset($this->values[$key]);

        return true;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->values);
    }

    public function clear(): bool
    {
        $this->values = [];

        return true;
    }
}

final class FixtureAuthProvider implements \YeAPF\Plugins\Auth\AuthProviderInterface
{
    public function authenticate(string $token): \YeAPF\Plugins\Auth\AuthResult
    {
        return new \YeAPF\Plugins\Auth\AuthResult(['token' => $token], 'fixture', null);
    }

    public function issue(array $claims): string
    {
        return 'fixture-token';
    }

    public function getProviderKey(): string
    {
        return 'fixture';
    }
}

final class FixtureTranslationProvider implements \YeAPF\Plugins\I18n\TranslationProviderInterface
{
    public function translate(string $tag, string $lang): string
    {
        return $tag . ':' . $lang;
    }

    public function getDefaultLang(): string
    {
        return 'en';
    }
}

final class FixtureLogHandler implements \YeAPF\Plugins\Log\LogHandlerInterface
{
    public function handle(string $level, string $message, array $context = []): void
    {
    }
}

final class FixtureDBDriver implements \YeAPF\Connection\DB\Driver\DBDriverInterface
{
    public function getEngineKey(): string
    {
        return 'fixture';
    }

    public function getDriverName(): string
    {
        return 'fixture';
    }

    public function getDriverVersion(): ?string
    {
        return '1.0';
    }

    public function getCapabilities(): \YeAPF\Connection\DB\Driver\DriverCapabilities
    {
        return new \YeAPF\Connection\DB\Driver\DriverCapabilities();
    }

    public function execute(string $sql, array $params = []): int
    {
        return 0;
    }

    public function fetchOne(string $sql, array $params = []): ?array
    {
        return null;
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        return [];
    }

    public function beginTransaction(): void
    {
    }

    public function commit(): void
    {
    }

    public function rollBack(): void
    {
    }

    public function normalizeError(Throwable $throwable, ?string $sql = null, array $params = []): array
    {
        return [
            'driver' => 'fixture',
            'sql_state' => null,
            'driver_code' => null,
            'message' => $throwable->getMessage(),
            'normalized_code' => \YeAPF\Connection\DB\Driver\NormalizedDbError::CODE_INTERNAL_UNKNOWN,
            'is_transient' => false,
            'context' => ['sql' => $sql, 'params' => $params],
        ];
    }
}
