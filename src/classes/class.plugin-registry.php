<?php declare(strict_types=1);

namespace YeAPF\Plugins;

use YeAPF\Connection\DB\Driver\DBDriverInterface;
use YeAPF\Plugins\Auth\AuthProviderInterface;
use YeAPF\Plugins\Cache\CacheProviderInterface;
use YeAPF\Plugins\I18n\TranslationProviderInterface;
use YeAPF\Plugins\Log\LogHandlerInterface;
use YeAPF\Plugins\Type\TypeProviderInterface;
use YeAPF\Plugins\Validator\DocumentValidatorInterface;

class Registry
{
    private static bool $frozen = false;

    /** @var array<string,DocumentValidatorInterface> */
    private static array $documentValidators = [];

    /** @var array<int,TypeProviderInterface> */
    private static array $typeProviders = [];

    /** @var array<string,DBDriverInterface> */
    private static array $dbDrivers = [];

    private static ?CacheProviderInterface $cacheProvider = null;
    private static ?AuthProviderInterface $authProvider = null;
    private static ?TranslationProviderInterface $translationProvider = null;
    private static ?LogHandlerInterface $logHandler = null;

    public static function registerDocumentValidator(DocumentValidatorInterface $validator): void
    {
        self::assertNotFrozen();
        foreach ($validator->getSupportedKeys() as $key) {
            self::$documentValidators[(string) $key] = $validator;
        }
    }

    public static function getDocumentValidator(string $key): ?DocumentValidatorInterface
    {
        return self::$documentValidators[$key] ?? null;
    }

    public static function registerTypeProvider(TypeProviderInterface $provider): void
    {
        self::assertNotFrozen();
        self::$typeProviders[] = $provider;
    }

    /**
     * @return array<int,TypeProviderInterface>
     */
    public static function getTypeProviders(): array
    {
        return self::$typeProviders;
    }

    public static function registerDBDriver(DBDriverInterface $driver): void
    {
        self::assertNotFrozen();
        $engineKey = self::resolveEngineKey($driver);
        self::$dbDrivers[$engineKey] = $driver;
    }

    public static function getDBDriver(string $engineKey): ?DBDriverInterface
    {
        return self::$dbDrivers[$engineKey] ?? null;
    }

    public static function registerCacheProvider(CacheProviderInterface $provider): void
    {
        self::assertNotFrozen();
        self::$cacheProvider = $provider;
    }

    public static function getCacheProvider(): ?CacheProviderInterface
    {
        return self::$cacheProvider;
    }

    public static function registerAuthProvider(AuthProviderInterface $provider): void
    {
        self::assertNotFrozen();
        self::$authProvider = $provider;
    }

    public static function getAuthProvider(): ?AuthProviderInterface
    {
        return self::$authProvider;
    }

    public static function registerTranslationProvider(TranslationProviderInterface $provider): void
    {
        self::assertNotFrozen();
        self::$translationProvider = $provider;
    }

    public static function getTranslationProvider(): ?TranslationProviderInterface
    {
        return self::$translationProvider;
    }

    public static function registerLogHandler(LogHandlerInterface $handler): void
    {
        self::assertNotFrozen();
        self::$logHandler = $handler;
    }

    public static function getLogHandler(): ?LogHandlerInterface
    {
        return self::$logHandler;
    }

    public static function freeze(): void
    {
        if (self::$frozen) {
            return;
        }

        foreach (self::$typeProviders as $provider) {
            foreach ($provider->getTypeDefinitions() as $typeName => $definition) {
                \YeAPF\BasicTypes::set((string) $typeName, $definition);
            }
        }

        self::$frozen = true;
    }

    public static function isFrozen(): bool
    {
        return self::$frozen;
    }

    private static function assertNotFrozen(): void
    {
        if (!self::$frozen) {
            return;
        }

        throw new \YeAPF\YeAPFException(
            'PluginRegistry is frozen. Plugins must be registered at boot time only.',
            YeAPF_PLUGIN_REGISTRY_FROZEN
        );
    }

    private static function resolveEngineKey(DBDriverInterface $driver): string
    {
        if (method_exists($driver, 'getEngineKey')) {
            $engineKey = (string) $driver->getEngineKey();
            if ('' !== trim($engineKey)) {
                return $engineKey;
            }
        }

        return strtolower($driver->getDriverName());
    }
}
