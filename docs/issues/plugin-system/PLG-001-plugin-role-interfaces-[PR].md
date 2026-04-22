# PLG-001 — Define plugin role interfaces

## Files to create
- `src/classes/class.plugin-role-interfaces.php`

## Problem
The current `ServicePluginInterface` mandates only `registerServiceMethods($server)`, which is meaningless for validators, type providers, DB drivers, cache backends, auth providers, translation providers, or log handlers. There is no typed contract any of those roles must satisfy.

## What to do

### `YeAPF\Plugins\PluginRoleInterface`
Marker interface. All role-specific interfaces extend this.

```php
namespace YeAPF\Plugins;
interface PluginRoleInterface {}
```

---

### `YeAPF\Plugins\Validator\DocumentValidatorInterface`
```php
namespace YeAPF\Plugins\Validator;
interface DocumentValidatorInterface extends \YeAPF\Plugins\PluginRoleInterface
{
    /** Returns the registry keys this validator handles. e.g. ['BR.CNPJ', 'BR.CPF'] */
    public function getSupportedKeys(): array;

    /**
     * Validates authenticity of $value for the given $key.
     * Returns true if valid, false if invalid.
     * Must not throw for ordinary validation failures — only for contract violations.
     */
    public function validate(string $key, string $value): bool;
}
```

---

### `YeAPF\Plugins\Type\TypeProviderInterface`
```php
namespace YeAPF\Plugins\Type;
interface TypeProviderInterface extends \YeAPF\Plugins\PluginRoleInterface
{
    /**
     * Returns constraint arrays to be fed into BasicTypes::set().
     * Keys are type names (e.g. 'CNPJ'), values are constraint arrays.
     * Each constraint array may include 'authenticityChecker' => 'BR.CNPJ'.
     */
    public function getTypeDefinitions(): array;
}
```

---

### `YeAPF\Plugins\Cache\CacheProviderInterface`
Thin abstraction over PSR-16 simple cache. One implementation (Redis) ships with the framework; others can be added by plugins.

```php
namespace YeAPF\Plugins\Cache;
interface CacheProviderInterface extends \YeAPF\Plugins\PluginRoleInterface
{
    public function get(string $key, mixed $default = null): mixed;
    public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool;
    public function delete(string $key): bool;
    public function has(string $key): bool;
    public function clear(): bool;
}
```

Note: method signatures are intentionally compatible with PSR-16 `CacheInterface` to allow future drop-in replacement, but this interface does not extend `Psr\SimpleCache\CacheInterface` directly — keeping the PSR dependency optional per the non-intrusive principle.

---

### `YeAPF\Plugins\Auth\AuthProviderInterface`
```php
namespace YeAPF\Plugins\Auth;
interface AuthProviderInterface extends \YeAPF\Plugins\PluginRoleInterface
{
    /**
     * Attempt to authenticate from a raw token string (JWT, API key, etc.).
     * Returns an AuthResult value object on success.
     * Throws \YeAPF\YeAPFException with YeAPF_AUTH_FAILED on failure.
     */
    public function authenticate(string $token): AuthResult;

    /**
     * Issue a new token for the given claims array.
     * Returns the token string.
     */
    public function issue(array $claims): string;

    /** Returns a short identifier for this provider e.g. 'jwt', 'apikey'. */
    public function getProviderKey(): string;
}
```

`AuthResult` is a simple value object: `{ claims: array, providedBy: string, expiresAt: ?int }`.

---

### `YeAPF\Plugins\I18n\TranslationProviderInterface`
```php
namespace YeAPF\Plugins\I18n;
interface TranslationProviderInterface extends \YeAPF\Plugins\PluginRoleInterface
{
    /**
     * Translate $tag into $lang.
     * Returns the translated string, or $tag itself if no translation found.
     */
    public function translate(string $tag, string $lang): string;

    /** Returns the default language code this provider serves. */
    public function getDefaultLang(): string;
}
```

---

### `YeAPF\Plugins\Log\LogHandlerInterface`
PSR-3 compatible sink. The framework logger core remains internal; only the output destination is pluggable.

```php
namespace YeAPF\Plugins\Log;
interface LogHandlerInterface extends \YeAPF\Plugins\PluginRoleInterface
{
    /**
     * Write a log entry. $level matches PSR-3 LogLevel constants.
     * $context is an arbitrary key-value array (PSR-3 §1.2).
     */
    public function handle(string $level, string $message, array $context = []): void;
}
```

---

## Deferred
- `SerializerInterface` — Protobuf support in collections must be fixed and stabilized (CQR-002) before a serializer plugin role makes sense. Revisit after CQR-002 is closed.

## Acceptance criteria
- All interfaces exist under the correct namespaces
- `declare(strict_types=1)` present
- PSR-4 autoload path coherent with `composer.json`
- No concrete logic — interfaces and value objects only
- PHPUnit smoke test: each interface is loadable; declared methods confirmed via reflection
- `AuthResult` value object is immutable (all properties set in constructor, no setters)

## Constants needed (define in `yeapf-definitions.php`)
- `YeAPF_AUTHENTICITY_CHECK_FAILED`
- `YeAPF_AUTH_FAILED`
- `YeAPF_PLUGIN_REGISTRY_FROZEN`

## Notes
- `ServicePluginInterface` is **not** removed — it serves HTTP2 service registration. A plugin may implement multiple interfaces.
- `DBDriverInterface` (defined in `db-driver-contract` epic) extends `PluginRoleInterface`. That dependency is owned by `db-driver-contract`; no changes here.
