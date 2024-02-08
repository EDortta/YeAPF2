<?php declare(strict_types=1);

namespace YeAPF;

(function () {
    $myDocumentModel = new \YeAPF\SanitizedKeyData();

    $myDocumentModel->setConstraint(
        keyName: 'string',
        keyType: YeAPF_TYPE_STRING,
        length: 256,
    );

    $myDocumentModel->setConstraint(
        keyName: 'short',
        keyType: YeAPF_TYPE_INT,
        minValue: - 32767,
        maxValue: 32767
    );

    $myDocumentModel->setConstraint(
        keyName: 'unsignedShort',
        keyType: YeAPF_TYPE_INT,
        minValue: 0,
        maxValue: 65535
    );

    $myDocumentModel->setConstraint(
        keyName: 'long',
        keyType: YeAPF_TYPE_INT,
        minValue: - 2147483647,
        maxValue: 2147483647
    );

    $myDocumentModel->setConstraint(
        keyName: 'unsignedLong',
        keyType: YeAPF_TYPE_INT,
        minValue: 0,
        maxValue: 4294967295
    );

    $myDocumentModel->setConstraint(
        keyName: 'float',
        keyType: YeAPF_TYPE_FLOAT,
        length: 16,
        decimals: 2
    );

    $myDocumentModel->setConstraint(
        keyName: 'date',
        keyType: YeAPF_TYPE_DATE
    );

    $myDocumentModel->setConstraint(
        keyName: 'time',
        keyType: YeAPF_TYPE_TIME
    );

    $myDocumentModel->setConstraint(
        keyName: 'datetime',
        keyType: YeAPF_TYPE_DATETIME
    );

    $myDocumentModel->setConstraint(
        keyName: 'json',
        keyType: YeAPF_TYPE_JSON
    );

    $myDocumentModel->setConstraint(
        keyName: 'bool',
        keyType: YeAPF_TYPE_BOOL
    );

    $myDocumentModel->setConstraint(
        keyName: 'email',
        keyType: YeAPF_TYPE_STRING,
        length: 256,
        regExpression: YeAPF_EMAIL_REGEX
    );

    $myDocumentModel->setConstraint(
        keyName: 'cnpj',
        keyType: YeAPF_TYPE_STRING,
        length: 14,
        sedOutputExpression: YeAPF_SED_BR_OUT_CNPJ
    );

    $myDocumentModel->setConstraint(
        keyName: 'cpf',
        keyType: YeAPF_TYPE_STRING,
        length: 11,
        sedOutputExpression: YeAPF_SED_BR_OUT_CPF
    );

    $code = "<?php declare(strict_types=1);\nnamespace YeAPF;\n";
    $code .= "class BasicTypes {\n";
    $code .= "\tprivate static \$basicTypes = [];\n";
    $code .= "\tpublic static function startup() {\n";
    $code .= "\t\tself::\$basicTypes = [\n";

    foreach ($myDocumentModel->getConstraints() as $keyName => $keyDefinition) {
        $code .= "\t\t\t'" . $keyName . "' => [\n";
        foreach ($keyDefinition as $key => $value) {
            if ($value != null) {
                if (!is_numeric($value))
                    $value = "'$value'";
                $code .= "\t\t\t\t'$key' => $value,\n";
            }
        }
        $code .= "\t\t\t],\n\n";
    }
    $code .= "\t\t];\n\t}\n";

    $code .= "\tpublic static function get(\$keyName) {\n\t\treturn self::\$basicTypes[\$keyName]??null;\n\t}\n";

    $code .= "\tpublic static function list() {\n\t\treturn array_keys(self::\$basicTypes);\n\t}\n";

    $code .= "\tpublic static function set(\$keyName, \$definition) {\n\t\tself::\$basicTypes[\$keyName] = \$definition;\n\t}\n";

    $code .= "}\nBasicTypes::startup();\n";

    file_put_contents(__DIR__ . '/yTypes.php', $code);

})();
