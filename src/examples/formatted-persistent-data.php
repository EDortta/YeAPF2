<?php declare(strict_types=1);

require_once __DIR__ . '/../yeapf-core.php';

$myDocumentModel = new \YeAPF\SanitizedKeyData();

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


$myObject = clone $myDocumentModel;
$myObject->cnpj='83323890000108';

echo "CNPJ = ".$myObject->cnpj."\n";