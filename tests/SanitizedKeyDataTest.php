<?php declare(strict_types=1);

require_once __DIR__ . '/../src/yeapf-core.php';

use PHPUnit\Framework\TestCase;
use YeAPF\SanitizedKeyData;

final class SanitizedKeyDataTest extends TestCase
{
    private SanitizedKeyData $obj;

    protected function setUp(): void
    {
        $this->obj = new SanitizedKeyData();
    }

    public function testSetStringConstraint(): void
    {
        $this->obj->setConstraint('name', YeAPF_TYPE_STRING, false, 50);

        $this->assertSame(
            [
                'type' => YeAPF_TYPE_STRING,
                'length' => 50,
                'decimals' => null,
                'acceptNULL' => false,
                'minValue' => null,
                'maxValue' => null,
                'regExpression' => YeAPF_STRING_REGEX,
                'sedInputExpression' => null,
                'sedOutputExpression' => null,
                'unique' => false,
                'required' => false,
                'primary' => false,
                'protobufOrder' => null,
                'tag' => ';;',
                'defaultValue' => null,
                'authenticityChecker' => null,
            ],
            $this->obj->getConstraints()['name']
        );
    }

    public function testSetIntegerConstraint(): void
    {
        $this->obj->setConstraint('age', YeAPF_TYPE_INT, true, null, null, 0, 100);

        $this->assertSame(
            [
                'type' => YeAPF_TYPE_INT,
                'length' => null,
                'decimals' => null,
                'acceptNULL' => true,
                'minValue' => 0.0,
                'maxValue' => 100,
                'regExpression' => YeAPF_INT_REGEX,
                'sedInputExpression' => null,
                'sedOutputExpression' => null,
                'unique' => false,
                'required' => false,
                'primary' => false,
                'protobufOrder' => null,
                'tag' => ';;',
                'defaultValue' => null,
                'authenticityChecker' => null,
            ],
            $this->obj->getConstraints()['age']
        );
    }

    public function testCheckValidStringConstraint(): void
    {
        $this->obj->setConstraint('name', YeAPF_TYPE_STRING, false, 10);
        $this->assertSame('John Doe', $this->obj->checkConstraint('name', 'John Doe'));
    }

    public function testInvalidStringConstraintThrowsException(): void
    {
        $this->obj->setConstraint('name', YeAPF_TYPE_STRING, false, 10);
        $this->expectException(YeAPF\YeAPFException::class);
        $this->obj->checkConstraint('name', 'This string is too long');
    }

    public function testValidIntegerConstraint(): void
    {
        $this->obj->setConstraint('age', YeAPF_TYPE_INT, true, null, null, 18, 65);
        $this->assertSame(25, $this->obj->checkConstraint('age', 25));
    }

    public function testInvalidIntegerConstraintThrowsException(): void
    {
        $this->obj->setConstraint('age', YeAPF_TYPE_INT, true, null, null, 18, 65);
        $this->expectException(YeAPF\YeAPFException::class);
        $this->obj->checkConstraint('age', 70);
    }

    public function testImportConstraintsAndEnforceBounds(): void
    {
        $constraints = [
            'name' => [
                'type' => YeAPF_TYPE_STRING,
                'length' => 50,
                'acceptNULL' => false,
            ],
            'email' => [
                'type' => YeAPF_TYPE_STRING,
                'length' => 100,
                'regExpression' => '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
                'acceptNULL' => true,
            ],
            'cep' => [
                'type' => YeAPF_TYPE_STRING,
                'length' => 8,
                'regExpression' => '/(\d{5})(\d{3})/',
                'acceptNULL' => false,
            ],
        ];

        $this->obj->importConstraints($constraints);

        $allConstraints = $this->obj->getConstraints();
        $this->assertArrayHasKey('name', $allConstraints);
        $this->assertArrayHasKey('email', $allConstraints);
        $this->assertArrayHasKey('cep', $allConstraints);
        $this->assertSame(8, $allConstraints['cep']['length']);

        $this->expectException(YeAPF\YeAPFException::class);
        $this->obj->cep = '1234567890';
    }

    public function testInputAndOutputCEPFormatter(): void
    {
        $this->obj->setConstraint(
            keyName: 'cep',
            keyType: YeAPF_TYPE_STRING,
            length: 8,
            acceptNULL: false,
            required: true,
            sedInputExpression: YeAPF_SED_BR_IN_POSTAL_CODE,
            regExpression: '/(\d{5})(\d{3})/',
            sedOutputExpression: YeAPF_SED_BR_OUT_POSTAL_CODE,
        );

        $this->obj->cep = '123.456-78';
        $this->assertSame('12345-678', $this->obj->cep);

        $this->obj->cep = '46x490-295';
        $this->assertSame('46490-295', $this->obj->cep);

        $this->expectException(YeAPF\YeAPFException::class);
        $this->obj->cep = '123456789';
    }

    public function testInputAndOutputCNPJFormatter(): void
    {
        $this->obj->setConstraint(
            keyName: 'cnpj',
            keyType: YeAPF_TYPE_STRING,
            length: 14,
            acceptNULL: false,
            required: true,
            sedInputExpression: YeAPF_SED_BR_IN_CNPJ,
            sedOutputExpression: YeAPF_SED_BR_OUT_CNPJ
        );

        $this->obj->cnpj = '12.345.678/0001-90';
        $this->assertSame('12.345.678/0001-90', $this->obj->cnpj);

        $this->obj->cnpj = '12345678000190';
        $this->assertSame('12.345.678/0001-90', $this->obj->cnpj);
    }

    public function testInputAndOutputCPFFormatter(): void
    {
        $this->obj->setConstraint(
            keyName: 'cpf',
            keyType: YeAPF_TYPE_STRING,
            length: 11,
            acceptNULL: false,
            required: true,
            sedInputExpression: YeAPF_SED_BR_IN_CPF,
            sedOutputExpression: YeAPF_SED_BR_OUT_CPF
        );

        $this->obj->cpf = '123.456.789-01';
        $this->assertSame('123.456.789-01', $this->obj->cpf);

        $this->obj->cpf = '12345678901';
        $this->assertSame('123.456.789-01', $this->obj->cpf);
    }

    public function testCheckAndSanitize(): void
    {
        $this->obj->setConstraint('name', YeAPF_TYPE_STRING, false, 51);

        $testValue = '<script>alert("XSS")</script>';
        $codedTestValue = '&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;';

        $constraintTest = $this->obj->checkConstraint('name', $testValue);
        $this->assertSame($codedTestValue, $constraintTest);

        $this->obj->name = $testValue;
        $this->assertSame($testValue, $this->obj->name);
        $this->assertSame($codedTestValue, $this->obj->__get_raw_value('name'));
    }
}
