<?php declare(strict_types=1);

require_once __DIR__ . '/../src/yeapf-core.php';

use PHPUnit\Framework\TestCase;

final class BrazilDocumentPluginTest extends TestCase
{
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
}
