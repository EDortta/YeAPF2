<?php declare(strict_types=1);

// require_once("vendor/autoload.php");
require_once __DIR__ . '/../src/yeapf-core.php';

use PHPUnit\Framework\TestCase;
use YeAPF\SanitizedKeyData;

class SanitizedKeyDataTest extends TestCase
{
    private SanitizedKeyData $obj;

    protected function setUp(): void
    {
        $this->obj = new SanitizedKeyData();
    }

    public function testSetStringConstraint()
    {
        $this->obj->setConstraint('name', YeAPF_TYPE_STRING, false, 50);
        $this->assertEquals([
                                'type'                => YeAPF_TYPE_STRING,
                                'length'              => 50,
                                'decimals'            => null,
                                'acceptNULL'          => false,
                                'minValue'            => null,
                                'maxValue'            => null,
                                'regExpression'       => YeAPF_STRING_REGEX,
                                'sedInputExpression'  => null,
                                'sedOutputExpression' => null,
                                'unique'              => false,
                                'required'            => false,
                                'primary'             => false,
                                'protobufOrder'       => null,
                                'tag'                 => ';;',
                                'defaultValue'        => null,
                                'authenticityChecker' => null
                            ], $this->obj->getConstraints()['name']);
    }

    public function testSetIntegerConstraint()
    {
        $this->obj->setConstraint('age', YeAPF_TYPE_INT, true, null, null, 0, 100);
        $this->assertEquals([
                                'type'                => YeAPF_TYPE_INT,
                                'length'              => null,
                                'decimals'            => null,
                                'acceptNULL'          => true,
                                'minValue'            => 0.0,
                                'maxValue'            => 100,
                                'regExpression'       => YeAPF_INT_REGEX,
                                'sedInputExpression'  => null,
                                'sedOutputExpression' => null,
                                'unique'              => false,
                                'required'            => false,
                                'primary'             => false,
                                'protobufOrder'       => null,
                                'tag'                 => ';;',
                                'defaultValue'        => null,
                                'authenticityChecker' => null
                            ], $this->obj->getConstraints()['age']);
    }

    public function testCheckValidStringConstraint()
    {
        $this->obj->setConstraint('name', YeAPF_TYPE_STRING, false, 10);
        $this->assertEquals('John Doe', $this->obj->checkConstraint('name', 'John Doe'));
    }

    public function testInvalidStringConstraintThrowsException()
    {
        $this->obj->setConstraint('name', YeAPF_TYPE_STRING, false, 10);
        $this->expectException(YeAPF\YeAPFException::class);
        $this->obj->checkConstraint('name', 'This string is too long');
    }

    public function testValidIntegerConstraint()
    {
        $this->obj->setConstraint('age', YeAPF_TYPE_INT, true, null, null, 18, 65);
        $this->assertEquals(25, $this->obj->checkConstraint('age', 25));
    }

    public function testInvalidIntegerConstraintThrowsException()
    {
        $this->obj->setConstraint('age', YeAPF_TYPE_INT, true, null, null, 18, 65);
        $this->expectException(YeAPF\YeAPFException::class);
        $this->obj->checkConstraint('age', 70);
    }

    public function testImportConstraints()
    {
        $constraints = [
            'name' => [
                'type'       => YeAPF_TYPE_STRING,
                'length'     => 50,
                'acceptNULL' => false
            ],
            'email' => [
                'type'          => YeAPF_TYPE_STRING,
                'length'        => 100,
                'regExpression' => '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
                'acceptNULL'    => true
            ],
            'cep' => [
                'type'          => YeAPF_TYPE_STRING,
                'length'        => 8,
                'regExpression' => '/(\d{5})(\d{3})/',
                'acceptNULL'    => false
            ]
        ];

        $this->obj->importConstraints($constraints);

        $allConstraints = $this->obj->getConstraints();

        /**
         * When defining constraints, the method add
         * some default characteristics to the constraints
         * in order to increase the security.
         * So, we can only compare the desired constraints.
         */
        $chosedConstraints = [];
        // foreach ($constraints as $key => $constraint) {
        //     $chosedConstraints[$key] = [];
        //     foreach ($constraint as $k => $v) {
        //         $this->assertEquals($chosedConstraints[$key], $allConstraints[$key][$k]??'ERROR!!!' );
        //     }
        // }

        // $this->assertEquals($constraints, $chosedConstraints);

        $this->expectException(YeAPF\YeAPFException::class);
        $this->obj->cep = '1234567890';

    }

    public function testInputAndOutputCEPFormatter()
    {
        $this->obj->setConstraint(
            keyName: 'cep',
            keyType: YeAPF_TYPE_STRING,
            length: 8,
            acceptNULL: false,
            required: true,
            sedInputExpression: YeAPF_SED_BR_IN_POSTAL_CODE,
            regExpression:  '/(\d{5})(\d{3})/',
            sedOutputExpression: YeAPF_SED_BR_OUT_POSTAL_CODE,
        );

        $this->obj->cep = '123.456-78';
        $this->assertEquals('12345-678', $this->obj->cep);

        $this->obj->cep='46x490-295';
        $this->assertEquals('46490-295', $this->obj->cep);

        $this->expectException(YeAPF\YeAPFException::class);
        $this->obj->cep = '123456789';

    }

    public function testInputAndOutputCNPJFormatter()
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
        $this->assertEquals('12.345.678/0001-90', $this->obj->cnpj);
    
        $this->obj->cnpj = '12345678000190';
        $this->assertEquals('12.345.678/0001-90', $this->obj->cnpj);
    }
    
    public function testInputAndOutputCPFFormatter()
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
        $this->assertEquals('123.456.789-01', $this->obj->cpf);
    
        $this->obj->cpf = '12345678901';
        $this->assertEquals('123.456.789-01', $this->obj->cpf);
    }
    

    public function testCheckAndSanitize()
    {
        $this->obj->setConstraint('name', YeAPF_TYPE_STRING, false, 51);

        $testValue      = '<script>alert("XSS")</script>';
        $codedTestValue = '&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;';

        // Test check
        $constraintTest = $this->obj->checkConstraint('name', $testValue);
        $this->assertEquals($codedTestValue, $constraintTest);

        // Test sanitize
        $this->obj->name = $testValue;

        $sanitizedValue = $this->obj->name;
        $this->assertEquals($testValue, $sanitizedValue);

        // Test raw value (as will be passed to database)
        $rawValue = $this->obj->__get_raw_value('name');
        $this->assertEquals($codedTestValue, $rawValue);
    }
}
