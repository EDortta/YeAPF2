<?php declare(strict_types=1);

require_once __DIR__ . '/../src/yeapf-core.php';

use PHPUnit\Framework\TestCase;

/**
 * Base contract for document-validator plugins.
 */
abstract class DocumentValidatorConformanceTestCase extends TestCase
{
    protected function setUp(): void
    {
        $this->resetAndBootstrapRegistry();
    }

    public function testRegistryLoadsValidatorForAllSupportedKeys(): void
    {
        $keys = $this->supportedKeys();
        $this->assertNotEmpty($keys);

        $firstValidator = \YeAPF\Plugins\Registry::getDocumentValidator($keys[0]);
        $this->assertNotNull($firstValidator);

        foreach ($keys as $key) {
            $validator = \YeAPF\Plugins\Registry::getDocumentValidator($key);
            $this->assertNotNull($validator, 'Expected validator for key: ' . $key);
            $this->assertSame($firstValidator, $validator, 'Expected same plugin instance for key: ' . $key);
        }
    }

    public function testValidFixturesPassValidation(): void
    {
        foreach ($this->validFixtures() as [$key, $value]) {
            $validator = \YeAPF\Plugins\Registry::getDocumentValidator($key);
            $this->assertNotNull($validator, 'Missing validator for key: ' . $key);
            $this->assertTrue($validator->validate($key, $value), $key . ':' . $value);
        }
    }

    public function testInvalidFixturesFailValidation(): void
    {
        foreach ($this->invalidFixtures() as [$key, $value]) {
            $validator = \YeAPF\Plugins\Registry::getDocumentValidator($key);
            $this->assertNotNull($validator, 'Missing validator for key: ' . $key);
            $this->assertFalse($validator->validate($key, $value), $key . ':' . $value);
        }
    }

    public function testTypeDefinitionsExposeAuthenticityCheckers(): void
    {
        foreach ($this->typeAuthenticityMap() as $typeName => $checkerKey) {
            $definition = \YeAPF\BasicTypes::get($typeName);
            $this->assertIsArray($definition, 'Missing type definition for: ' . $typeName);
            $this->assertSame($checkerKey, $definition['authenticityChecker'] ?? null, 'Wrong checker for: ' . $typeName);
        }
    }

    /**
     * @return list<string>
     */
    abstract protected function supportedKeys(): array;

    /**
     * @return list<array{0:string,1:string}>
     */
    abstract protected function validFixtures(): array;

    /**
     * @return list<array{0:string,1:string}>
     */
    abstract protected function invalidFixtures(): array;

    /**
     * @return array<string,string>
     */
    abstract protected function typeAuthenticityMap(): array;

    abstract protected function pluginClassName(): string;

    abstract protected function pluginFilePath(): string;

    private function resetAndBootstrapRegistry(): void
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

        $pluginClass = $this->pluginClassName();
        if (!class_exists($pluginClass)) {
            require_once $this->pluginFilePath();
        }

        /** @var object $plugin */
        $plugin = new $pluginClass();

        $this->assertInstanceOf(\YeAPF\Plugins\Validator\DocumentValidatorInterface::class, $plugin);
        $this->assertInstanceOf(\YeAPF\Plugins\Type\TypeProviderInterface::class, $plugin);

        \YeAPF\Plugins\Registry::registerDocumentValidator($plugin);
        \YeAPF\Plugins\Registry::registerTypeProvider($plugin);
        \YeAPF\Plugins\Registry::freeze();
    }
}
