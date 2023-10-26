<?php
declare(strict_types=1);
require_once __DIR__.'/../yeapf-core.php';

$myContext = new \YeAPF\Connection\PersistenceContext(
    new \YeAPF\Connection\DB\RedisConnection(),
    new \YeAPF\Connection\DB\PDOConnection()
);


$myDocumentModel = new \YeAPF\ORM\DocumentModel(
    $myContext,
    "a_test"
);
$myDocumentModel->setConstraint(
    keyName: "id",
    keyType: YeAPF_TYPE_STRING,
    length: 36,
    primary: true,
    protobufOrder: 0
);
$myDocumentModel->setConstraint("name", YeAPF_TYPE_STRING, protobufOrder: 0);
$myDocumentModel->setConstraint("birthDate", YeAPF_TYPE_DATE, protobufOrder: 0, acceptNULL: true);
$myDocumentModel->setConstraint("ageAtHire", YeAPF_TYPE_INT, protobufOrder: 0, minValue:0);
$myDocumentModel->setConstraint("email", YeAPF_TYPE_STRING, regExpression: '^[a-zA-Z0-9.!#$%&’*+/=?^_`{|}~-]+@[a-zA-Z0-9-]+(?:\.[a-zA-Z0-9-]+)*$', protobufOrder: 0, acceptNULL: true);
$myDocumentModel->setConstraint("salary", YeAPF_TYPE_FLOAT, decimals: 2, length: 14, protobufOrder: 0);
$myDocumentModel->setConstraint("hired", YeAPF_TYPE_BOOL, protobufOrder: 0);


$persistentTest = new YeAPF\ORM\PersistentCollection(
    $myContext,
    "a_test",
    "id",
    $myDocumentModel
);

echo "JSON structure:\n".$persistentTest->exportDocumentModel(YeAPF_JSON_FORMAT)."\n\n";
echo "SQL structure:\n".$persistentTest->exportDocumentModel(YeAPF_SQL_FORMAT)."\n\n";
echo "PROTOBUF structure:\n".$persistentTest->exportDocumentModel(YeAPF_PROTOBUF_FORMAT)."\n\n";

echo "Looking a record by example\n";
$example = clone $persistentTest->getDocumentModel();
$example->name = "John Doe";
$auxRet = $persistentTest->subsetByExample($example,100);
if ($auxRet) {
    echo "Record found\n";
    print_r($auxRet);
    if(count($auxRet)>1) {
        echo "More than one record found\n";
        for($i=1;$i<count($auxRet);$i++) {
            $idToDelete = $auxRet[$i]['id'];
            echo "Deleting id: ".$idToDelete."\n";
            $persistentTest->deleteDocument($idToDelete);
        }
    }

    $tmpClone = clone $persistentTest->getDocumentModel();
    $tmpClone->importData($auxRet[0]);
    echo "Array data cloned into a new instance of the model\n";
    print_r($tmpClone);

} else {
    echo "Record not found\n";
    echo "Saving data into the persistent collection:\n";

    $data = clone $persistentTest->getDocumentModel();
    $data->name = "John Doe";
    $data->ageAtHire = 25;
    $data->salary = 1000;
    $data->hired = true;
    $data->birthDate = "1980-01-01";
    $data->email = "dFZ9W@example.com";

    $persistentTest->setDocument(null, $data);

    echo "New id: ".$data->id."\n";
}
