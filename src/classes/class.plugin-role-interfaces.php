<?php declare(strict_types=1);

namespace YeAPF\Plugins;

interface PluginRoleInterface
{
}

namespace YeAPF\Plugins\Validator;

interface DocumentValidatorInterface extends \YeAPF\Plugins\PluginRoleInterface
{
    /**
     * @return list<string>
     */
    public function getSupportedKeys(): array;

    public function validate(string $key, string $value): bool;
}

namespace YeAPF\Plugins\Type;

interface TypeProviderInterface extends \YeAPF\Plugins\PluginRoleInterface
{
    /**
     * @return array<string,array<string,mixed>>
     */
    public function getTypeDefinitions(): array;
}

namespace YeAPF\Plugins\Cache;

interface CacheProviderInterface extends \YeAPF\Plugins\PluginRoleInterface
{
    public function get(string $key, mixed $default = null): mixed;

    public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool;

    public function delete(string $key): bool;

    public function has(string $key): bool;

    public function clear(): bool;
}

namespace YeAPF\Plugins\Auth;

final class AuthResult
{
    /** @var array<string,mixed> */
    private array $claims;
    private string $providedBy;
    private ?int $expiresAt;

    /**
     * @param array<string,mixed> $claims
     */
    public function __construct(array $claims, string $providedBy, ?int $expiresAt = null)
    {
        $this->claims = $claims;
        $this->providedBy = $providedBy;
        $this->expiresAt = $expiresAt;
    }

    /**
     * @return array<string,mixed>
     */
    public function getClaims(): array
    {
        return $this->claims;
    }

    public function getProvidedBy(): string
    {
        return $this->providedBy;
    }

    public function getExpiresAt(): ?int
    {
        return $this->expiresAt;
    }
}

interface AuthProviderInterface extends \YeAPF\Plugins\PluginRoleInterface
{
    public function authenticate(string $token): AuthResult;

    /**
     * @param array<string,mixed> $claims
     */
    public function issue(array $claims): string;

    public function getProviderKey(): string;
}

namespace YeAPF\Plugins\I18n;

interface TranslationProviderInterface extends \YeAPF\Plugins\PluginRoleInterface
{
    public function translate(string $tag, string $lang): string;

    public function getDefaultLang(): string;
}

namespace YeAPF\Plugins\Log;

interface LogHandlerInterface extends \YeAPF\Plugins\PluginRoleInterface
{
    /**
     * @param array<string,mixed> $context
     */
    public function handle(string $level, string $message, array $context = []): void;
}
