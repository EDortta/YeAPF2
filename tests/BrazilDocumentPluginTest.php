<?php declare(strict_types=1);

require_once __DIR__ . '/DocumentValidatorConformanceTestCase.php';

final class BrazilDocumentPluginTest extends DocumentValidatorConformanceTestCase
{
    protected function supportedKeys(): array
    {
        return ['BR.CNPJ', 'BR.CPF'];
    }

    protected function validFixtures(): array
    {
        return [
            ['BR.CNPJ', '11.222.333/0001-81'],
            ['BR.CPF', '529.982.247-25'],
        ];
    }

    protected function invalidFixtures(): array
    {
        return [
            ['BR.CNPJ', '11.222.333/0001-82'],
            ['BR.CNPJ', '00.000.000/0000-00'],
            ['BR.CPF', '529.982.247-24'],
            ['BR.CPF', '111.111.111-11'],
        ];
    }

    protected function typeAuthenticityMap(): array
    {
        return [
            'CNPJ' => 'BR.CNPJ',
            'CPF' => 'BR.CPF',
        ];
    }

    protected function pluginClassName(): string
    {
        return 'BrazilDocumentPlugin';
    }

    protected function pluginFilePath(): string
    {
        return __DIR__ . '/../src/plugins/br-document-plugin.php';
    }

    public function testBrazilTypeDefinitionsKeepDigitOnlyRegex(): void
    {
        $cnpj = \YeAPF\BasicTypes::get('CNPJ');
        $cpf = \YeAPF\BasicTypes::get('CPF');

        $this->assertSame('/^\\d{14}$/', $cnpj['regExpression'] ?? null);
        $this->assertSame('/^\\d{11}$/', $cpf['regExpression'] ?? null);
    }
}
