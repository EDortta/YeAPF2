<?php declare(strict_types=1);

require_once __DIR__ . '/../src/yeapf-core.php';

use PHPUnit\Framework\TestCase;

final class AuthenticityCheckerTest extends TestCase
{
    protected function setUp(): void
    {
        $this->resetRegistry();
    }

    public function testValidValuePassesAuthenticityChecker(): void
    {
        \YeAPF\Plugins\Registry::registerDocumentValidator(new FixtureAuthenticityValidator());

        $data = new \YeAPF\SanitizedKeyData();
        $data->setConstraint(
            keyName: 'document',
            keyType: YeAPF_TYPE_STRING,
            length: 14,
            regExpression: '/^\d{14}$/',
            authenticityChecker: 'BR.CNPJ'
        );

        $data->document = '12345678000195';

        $this->assertSame('12345678000195', $data->document);
    }

    public function testStructurallyValidButAlgorithmicallyInvalidValueThrows(): void
    {
        \YeAPF\Plugins\Registry::registerDocumentValidator(new FixtureAuthenticityValidator());

        $data = new \YeAPF\SanitizedKeyData();
        $data->setConstraint(
            keyName: 'document',
            keyType: YeAPF_TYPE_STRING,
            length: 14,
            regExpression: '/^\d{14}$/',
            authenticityChecker: 'BR.CNPJ'
        );

        $this->expectException(\YeAPF\YeAPFException::class);
        $this->expectExceptionCode(YeAPF_AUTHENTICITY_CHECK_FAILED);

        $data->document = '12345678000196';
    }

    public function testMissingValidatorSkipsAuthenticityCheckGracefully(): void
    {
        $data = new \YeAPF\SanitizedKeyData();
        $data->setConstraint(
            keyName: 'document',
            keyType: YeAPF_TYPE_STRING,
            length: 14,
            regExpression: '/^\d{14}$/',
            authenticityChecker: 'BR.CNPJ'
        );

        $data->document = '12345678000196';

        $this->assertSame('12345678000196', $data->document);
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
    }
}

final class FixtureAuthenticityValidator implements \YeAPF\Plugins\Validator\DocumentValidatorInterface
{
    public function getSupportedKeys(): array
    {
        return ['BR.CNPJ'];
    }

    public function validate(string $key, string $value): bool
    {
        return 'BR.CNPJ' === $key && '12345678000195' === $value;
    }
}
