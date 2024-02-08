<?php declare(strict_types=1);

require_once __DIR__ . '/../yeapf-core.php';

$myDocumentModel = new \YeAPF\SanitizedKeyData();

// defined manually
$myDocumentModel->setConstraint(
    keyName: 'cnpj',
    keyType: YeAPF_TYPE_STRING,
    length: 14,
    primary: true,
    unique: true,
    required: true,
    protobufOrder: 2,
    sedOutputExpression: YeAPF_SED_BR_OUT_CNPJ,
    tag: 'query;login;register'
);

// defined using predefned basic types
$myDocumentModel->setConstraintFromJSON('cnpj2', \YeAPF\BasicTypes::get('cnpj'));


$myObject = clone $myDocumentModel;
$myObject->cnpj='83323890000108';
$myObject->cnpj2='26517217000167';

echo "CNPJ = ".$myObject->cnpj."\n";
echo "CNPJ2 = ".$myObject->cnpj2."\n\n";

echo "Constraint 'cnpj' as JSON:\n";
echo $myObject->getConstraintAsJSON('cnpj');
echo "\n\n";
echo "Constraint 'cnpj2' as JSON:\n";
echo $myObject->getConstraintAsJSON('cnpj2');

echo "\n\nKeys:";
print_r( $myObject->keys());

echo "\n\nAs Basic type\n";
print_r(\YeAPF\BasicTypes::get('cnpj'));

echo "\n\nAll basic types\n";
print_r(\YeAPF\BasicTypes::list());