# PLG-002 — PluginRegistry with role-keyed slots and boot-time freeze

## Files to create / modify
- `src/classes/class.plugin-registry.php` (new)
- `src/classes/class.plugins.php` (modify `PluginList::loadPlugins()` to call registry freeze)

## Problem
`PluginList` is a flat static array keyed by class name with no notion of role. It cannot answer "give me the cache provider" or "is the registry still open?". After the HTTP2 server starts, no new plugins should be accepted — but nothing enforces this today.

## What to do

### `YeAPF\Plugins\Registry`

```php
namespace YeAPF\Plugins;

class Registry
{
    private static bool $frozen = false;

    // role slots
    private static array $documentValidators = [];  // string $key => DocumentValidatorInterface
    private static array $typeProviders      = [];  // list of TypeProviderInterface
    private static array $dbDrivers          = [];  // string $engineKey => DBDriverInterface
    private static ?Cache\CacheProviderInterface  $cacheProvider       = null;
    private static ?Auth\AuthProviderInterface    $authProvider        = null;
    private static ?I18n\TranslationProviderInterface $translationProvider = null;
    private static ?Log\LogHandlerInterface       $logHandler          = null;

    // --- Document validators (keyed by 'BR.CNPJ' etc.) ---

    public static function registerDocumentValidator(Validator\DocumentValidatorInterface $v): void
    {
        self::assertNotFrozen();
        foreach ($v->getSupportedKeys() as $key) {
            self::$documentValidators[$key] = $v;
        }
    }

    public static function getDocumentValidator(string $key): ?Validator\DocumentValidatorInterface
    {
        return self::$documentValidators[$key] ?? null;
    }

    // --- Type providers ---

    public static function registerTypeProvider(Type\TypeProviderInterface $p): void
    {
        self::assertNotFrozen();
        self::$typeProviders[] = $p;
    }

    // --- DB drivers ---

    public static function registerDBDriver(\YeAPF\Connection\DB\Driver\DBDriverInterface $d): void
    {
        self::assertNotFrozen();
        self::$dbDrivers[$d->getEngineKey()] = $d;
    }

    public static function getDBDriver(string $engineKey): ?\YeAPF\Connection\DB\Driver\DBDriverInterface
    {
        return self::$dbDrivers[$engineKey] ?? null;
    }

    // --- Cache provider (singleton slot) ---

    public static function registerCacheProvider(Cache\CacheProviderInterface $p): void
    {
        self::assertNotFrozen();
        self::$cacheProvider = $p;
    }

    public static function getCacheProvider(): ?Cache\CacheProviderInterface
    {
        return self::$cacheProvider;
    }

    // --- Auth provider (singleton slot) ---

    public static function registerAuthProvider(Auth\AuthProviderInterface $p): void
    {
        self::assertNotFrozen();
        self::$authProvider = $p;
    }

    public static function getAuthProvider(): ?Auth\AuthProviderInterface
    {
        return self::$authProvider;
    }

    // --- Translation provider (singleton slot) ---

    public static function registerTranslationProvider(I18n\TranslationProviderInterface $p): void
    {
        self::assertNotFrozen();
        self::$translationProvider = $p;
    }

    public static function getTranslationProvider(): ?I18n\TranslationProviderInterface
    {
        return self::$translationProvider;
    }

    // --- Log handler (singleton slot) ---

    public static function registerLogHandler(Log\LogHandlerInterface $h): void
    {
        self::assertNotFrozen();
        self::$logHandler = $h;
    }

    public static function getLogHandler(): ?Log\LogHandlerInterface
    {
        return self::$logHandler;
    }

    // --- Freeze ---

    /** Called once by PluginList::loadPlugins() after all plugins are loaded. */
    public static function freeze(): void
    {
        if (self::$frozen) {
            return; // idempotent
        }
        foreach (self::$typeProviders as $provider) {
            foreach ($provider->getTypeDefinitions() as $typeName => $definition) {
                \YeAPF\BasicTypes::set($typeName, $definition);
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
        if (self::$frozen) {
            throw new \YeAPF\YeAPFException(
                'PluginRegistry is frozen. Plugins must be registered at boot time only.',
                YeAPF_PLUGIN_REGISTRY_FROZEN
            );
        }
    }
}
```

### `PluginList::loadPlugins()` modification
After `closedir()`:
```php
\YeAPF\Plugins\Registry::freeze();
```

### Singleton vs. multi-slot rationale
- **Cache, Auth, Translation, Log** — singleton slots. Exactly one implementation per role makes sense at runtime; second registration replaces first (last plugin loaded wins, consistent with file-load order being the programmer's responsibility).
- **Document validators** — keyed by document type string. Multiple plugins can each cover different countries.
- **DB drivers** — keyed by engine string (`'postgresql'`, `'mysql'`). Multiple live simultaneously.

## Acceptance criteria
- `freeze()` is idempotent: calling twice does not double-register types or throw
- Any `register*` call after `freeze()` throws `YeAPF_PLUGIN_REGISTRY_FROZEN`
- `getDocumentValidator('BR.CNPJ')` returns the registered validator or `null` (never throws)
- `getCacheProvider()` returns `null` if no cache plugin was loaded (callers must handle)
- PHPUnit: boot-order test confirms `BasicTypes::get('CNPJ')` reflects plugin-provided definition after `freeze()`
- PHPUnit: post-freeze registration throws correct exception

## Notes
- `PluginList` is kept intact for backward compatibility with `ServicePlugin`/`ServicePluginInterface`; `Registry` is additive alongside it
- `DBDriverInterface` lives in `db-driver-contract` epic; the registry slot is declared here but the interface type is owned there. Build order: PLG-001+PLG-002 must be merged before `db-driver-contract` wires in
