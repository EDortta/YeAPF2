<?php declare(strict_types=1);

require_once __DIR__ . '/../src/yeapf-core.php';

use PHPUnit\Framework\TestCase;

final class BrazilDocumentPluginTest extends TestCase
{
    protected function setUp(): void
    {
        $this->resetAndBootstrapRegistry();
    }

    public function testRegistryLoadsBrazilDocumentValidator(): void
    {
        $cnpjValidator = \YeAPF\Plugins\Registry::getDocumentValidator('BR.CNPJ');
        $cpfValidator = \YeAPF\Plugins\Registry::getDocumentValidator('BR.CPF');

        $this->assertNotNull($cnpjValidator);
        $this->assertNotNull($cpfValidator);
        $this->assertSame($cnpjValidator, $cpfValidator);
    }

    public function testValidDocumentsPassValidation(): void
    {
        $validator = \YeAPF\Plugins\Registry::getDocumentValidator('BR.CNPJ');
        $this->assertNotNull($validator);

        $this->assertTrue($validator->validate('BR.CNPJ', '11.222.333/0001-81'));
        $this->assertTrue($validator->validate('BR.CPF', '529.982.247-25'));
    }

    public function testInvalidDocumentsFailValidation(): void
    {
        $validator = \YeAPF\Plugins\Registry::getDocumentValidator('BR.CNPJ');
        $this->assertNotNull($validator);

        $this->assertFalse($validator->validate('BR.CNPJ', '11.222.333/0001-82'));
        $this->assertFalse($validator->validate('BR.CNPJ', '00.000.000/0000-00'));
        $this->assertFalse($validator->validate('BR.CPF', '529.982.247-24'));
        $this->assertFalse($validator->validate('BR.CPF', '111.111.111-11'));
    }

    public function testPluginTypesOverwriteBasicTypesDefinitionsWithAuthenticityChecker(): void
    {
        $cnpj = \YeAPF\BasicTypes::get('CNPJ');
        $cpf = \YeAPF\BasicTypes::get('CPF');

        $this->assertIsArray($cnpj);
        $this->assertIsArray($cpf);
        $this->assertSame('BR.CNPJ', $cnpj['authenticityChecker'] ?? null);
        $this->assertSame('BR.CPF', $cpf['authenticityChecker'] ?? null);
        $this->assertSame('/^\d{14}$/', $cnpj['regExpression'] ?? null);
        $this->assertSame('/^\d{11}$/', $cpf['regExpression'] ?? null);
    }

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

        if (!class_exists('BrazilDocumentPlugin')) {
            require_once __DIR__ . '/../src/plugins/br-document-plugin.php';
        }

        $plugin = new BrazilDocumentPlugin();
        \YeAPF\Plugins\Registry::registerDocumentValidator($plugin);
        \YeAPF\Plugins\Registry::registerTypeProvider($plugin);
        \YeAPF\Plugins\Registry::freeze();
    }
}
