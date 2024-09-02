<?php declare(strict_types=1);

namespace YeAPF;

(function () {
    $myDocumentModel = new \YeAPF\SanitizedKeyData();

    $myDocumentModel->setConstraint(
        keyName: YeAPF_TYPE_STRING,
        keyType: YeAPF_TYPE_STRING,
        length: 256,
    );

    $myDocumentModel->setConstraint(
        keyName: 'SHORT',
        keyType: YeAPF_TYPE_INT,
        minValue: - 32767,
        maxValue: 32767
    );

    $myDocumentModel->setConstraint(
        keyName: 'UNSIGNEDSHORT',
        keyType: YeAPF_TYPE_INT,
        minValue: 0,
        maxValue: 65535
    );

    $myDocumentModel->setConstraint(
        keyName: 'LONG',
        keyType: YeAPF_TYPE_INT,
        minValue: - 2147483647,
        maxValue: 2147483647
    );

    $myDocumentModel->setConstraint(
        keyName: 'UNSIGNEDLONG',
        keyType: YeAPF_TYPE_INT,
        minValue: 0,
        maxValue: 4294967295
    );

    $myDocumentModel->setConstraint(
        keyName: YeAPF_TYPE_FLOAT,
        keyType: YeAPF_TYPE_FLOAT,
        length: 16,
        decimals: 2
    );

    $myDocumentModel->setConstraint(
        keyName: YeAPF_TYPE_DATE,
        keyType: YeAPF_TYPE_DATE
    );

    $myDocumentModel->setConstraint(
        keyName: YeAPF_TYPE_TIME,
        keyType: YeAPF_TYPE_TIME
    );

    $myDocumentModel->setConstraint(
        keyName: YeAPF_TYPE_DATETIME,
        keyType: YeAPF_TYPE_DATETIME
    );

    $myDocumentModel->setConstraint(
        keyName: YeAPF_TYPE_JSON,
        keyType: YeAPF_TYPE_JSON
    );

    $myDocumentModel->setConstraint(
        keyName: YeAPF_TYPE_BOOL,
        keyType: YeAPF_TYPE_BOOL
    );

    /**
     * Specialized types
     */

    $myDocumentModel->setConstraint(
        keyName: 'EMAIL',
        keyType: YeAPF_TYPE_STRING,
        length: 256,
        regExpression: YeAPF_EMAIL_REGEX
    );

    $myDocumentModel->setConstraint(
        keyName: 'ID',
        keyType: YeAPF_TYPE_STRING,
        length: 48,
        regExpression: YeAPF_ID_REGEX
    );

    $myDocumentModel->setConstraint(
        keyName: 'CNPJ',
        keyType: YeAPF_TYPE_STRING,
        length: 14,
        sedInputExpression: YeAPF_SED_BR_IN_CNPJ,
        sedOutputExpression: YeAPF_SED_BR_OUT_CNPJ
    );

    $myDocumentModel->setConstraint(
        keyName: 'CPF',
        keyType: YeAPF_TYPE_STRING,
        length: 11,
        sedOutputExpression: YeAPF_SED_BR_OUT_CPF
    );

    $code = "<?php declare(strict_types=1);\nnamespace YeAPF;\n";
    $code .= "/**\n * This file is generated by 'yGenerateBasicTypes.php'\n */\n\n";
    $code .= "class BasicTypes {\n";
    $code .= "\tprivate static \$basicTypes = [];\n";
    $code .= "\tpublic static function startup() {\n";
    $code .= "\t\tself::\$basicTypes = [\n";

    foreach ($myDocumentModel->getConstraints() as $keyName => $keyDefinition) {
        $code .= "\t\t\t'" . mb_strtoupper($keyName) . "' => [\n";
        foreach ($keyDefinition as $key => $value) {
            if ($value !== null) {
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

    $code .= "\tpublic static function set(\$keyName, \$definition) {\n\t\tself::\$basicTypes[mb_strtoupper(\$keyName)] = \$definition;\n\t}\n";

    $code .= "}\nBasicTypes::startup();\n";

    if (is_writable(__DIR__)) {
        if (!file_exists(__DIR__ . '/yTypes.php') || is_writable(__DIR__ . '/yTypes.php')) {
            file_put_contents(__DIR__ . '/yTypes.php', $code);
        } else {
            _trace('Cannot write to file ' . __DIR__ . '/yTypes.php');
            throw new \Exception('Cannot write to file ' . __DIR__ . '/yTypes.php');
        }
    } else {
        _trace('Cannot write to folder ' . __DIR__ );
        throw new \Exception('Cannot write to folder '.__DIR__);
    }

})();
