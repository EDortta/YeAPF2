<?php declare(strict_types=1);

require_once __DIR__ . '/../src/yeapf-core.php';

use PHPUnit\Framework\TestCase;

final class PluginRoleInterfacesContractTest extends TestCase
{
    public function testPluginRoleInterfacesAreLoadable(): void
    {
        $this->assertTrue(interface_exists(\YeAPF\Plugins\PluginRoleInterface::class));
        $this->assertTrue(interface_exists(\YeAPF\Plugins\Validator\DocumentValidatorInterface::class));
        $this->assertTrue(interface_exists(\YeAPF\Plugins\Type\TypeProviderInterface::class));
        $this->assertTrue(interface_exists(\YeAPF\Plugins\Cache\CacheProviderInterface::class));
        $this->assertTrue(interface_exists(\YeAPF\Plugins\Auth\AuthProviderInterface::class));
        $this->assertTrue(interface_exists(\YeAPF\Plugins\I18n\TranslationProviderInterface::class));
        $this->assertTrue(interface_exists(\YeAPF\Plugins\Log\LogHandlerInterface::class));
        $this->assertTrue(class_exists(\YeAPF\Plugins\Auth\AuthResult::class));
    }

    public function testDocumentValidatorContract(): void
    {
        $reflection = new ReflectionClass(\YeAPF\Plugins\Validator\DocumentValidatorInterface::class);

        $this->assertMethodSignature($reflection, 'getSupportedKeys', 0, 'array');
        $this->assertMethodSignature($reflection, 'validate', 2, 'bool', ['key' => 'string', 'value' => 'string']);
    }

    public function testTypeProviderContract(): void
    {
        $reflection = new ReflectionClass(\YeAPF\Plugins\Type\TypeProviderInterface::class);
        $this->assertMethodSignature($reflection, 'getTypeDefinitions', 0, 'array');
    }

    public function testCacheProviderContract(): void
    {
        $reflection = new ReflectionClass(\YeAPF\Plugins\Cache\CacheProviderInterface::class);

        $this->assertMethodSignature($reflection, 'get', 2, 'mixed', ['key' => 'string', 'default' => 'mixed']);
        $this->assertMethodSignature($reflection, 'set', 3, 'bool', ['key' => 'string', 'value' => 'mixed', 'ttl' => 'DateInterval|int|null']);
        $this->assertMethodSignature($reflection, 'delete', 1, 'bool', ['key' => 'string']);
        $this->assertMethodSignature($reflection, 'has', 1, 'bool', ['key' => 'string']);
        $this->assertMethodSignature($reflection, 'clear', 0, 'bool');
    }

    public function testAuthProviderContractAndImmutableAuthResult(): void
    {
        $reflection = new ReflectionClass(\YeAPF\Plugins\Auth\AuthProviderInterface::class);

        $this->assertMethodSignature($reflection, 'authenticate', 1, \YeAPF\Plugins\Auth\AuthResult::class, ['token' => 'string']);
        $this->assertMethodSignature($reflection, 'issue', 1, 'string', ['claims' => 'array']);
        $this->assertMethodSignature($reflection, 'getProviderKey', 0, 'string');

        $result = new \YeAPF\Plugins\Auth\AuthResult(['sub' => 'user-1'], 'jwt', 1710000000);
        $this->assertSame(['sub' => 'user-1'], $result->getClaims());
        $this->assertSame('jwt', $result->getProvidedBy());
        $this->assertSame(1710000000, $result->getExpiresAt());

        $resultReflection = new ReflectionClass(\YeAPF\Plugins\Auth\AuthResult::class);
        foreach ($resultReflection->getMethods() as $method) {
            $this->assertFalse(str_starts_with($method->getName(), 'set'));
        }

        foreach ($resultReflection->getProperties() as $property) {
            $this->assertTrue($property->isPrivate());
        }
    }

    public function testTranslationAndLogContracts(): void
    {
        $translationReflection = new ReflectionClass(\YeAPF\Plugins\I18n\TranslationProviderInterface::class);
        $this->assertMethodSignature($translationReflection, 'translate', 2, 'string', ['tag' => 'string', 'lang' => 'string']);
        $this->assertMethodSignature($translationReflection, 'getDefaultLang', 0, 'string');

        $logReflection = new ReflectionClass(\YeAPF\Plugins\Log\LogHandlerInterface::class);
        $this->assertMethodSignature($logReflection, 'handle', 3, 'void', ['level' => 'string', 'message' => 'string', 'context' => 'array']);
    }

    public function testPluginConstantsExist(): void
    {
        $this->assertTrue(defined('YeAPF_AUTHENTICITY_CHECK_FAILED'));
        $this->assertTrue(defined('YeAPF_AUTH_FAILED'));
        $this->assertTrue(defined('YeAPF_PLUGIN_REGISTRY_FROZEN'));
    }

    /**
     * @param array<string,string> $expectedParameterTypes
     */
    private function assertMethodSignature(
        ReflectionClass $reflection,
        string $methodName,
        int $expectedParameterCount,
        string $expectedReturnType,
        array $expectedParameterTypes = []
    ): void {
        $this->assertTrue($reflection->hasMethod($methodName));

        $method = $reflection->getMethod($methodName);
        $this->assertCount($expectedParameterCount, $method->getParameters());
        $this->assertSame($expectedReturnType, $this->normalizeTypeName($method->getReturnType()));

        $parametersByName = [];
        foreach ($method->getParameters() as $parameter) {
            $parametersByName[$parameter->getName()] = $parameter;
        }

        foreach ($expectedParameterTypes as $parameterName => $expectedType) {
            $this->assertArrayHasKey($parameterName, $parametersByName);
            $this->assertSame($expectedType, $this->normalizeTypeName($parametersByName[$parameterName]->getType()));
        }
    }

    private function normalizeTypeName(?ReflectionType $type): string
    {
        if (null === $type) {
            return 'mixed';
        }

        if ($type instanceof ReflectionNamedType) {
            $name = $type->getName();
            if ($type->allowsNull() && 'mixed' !== $name && 'null' !== $name && 'void' !== $name) {
                return '?' . $name;
            }

            return $name;
        }

        if ($type instanceof ReflectionUnionType) {
            $parts = [];
            foreach ($type->getTypes() as $unionType) {
                $parts[] = $unionType->getName();
            }
            sort($parts);

            return implode('|', $parts);
        }

        return (string) $type;
    }
}
