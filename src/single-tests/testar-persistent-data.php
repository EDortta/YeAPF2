<?php declare(strict_types=1);

error_reporting(-1);
ini_set('display_errors', 'On');

$echoSteps=false;
if ($echoSteps) echo '-> ' . __LINE__ . "\n";
require_once __DIR__ . '/../yeapf-core.php';
if ($echoSteps) echo '-> ' . __LINE__ . "\n";
\YeAPF\yLogger::defineLogFilters([], YeAPF_LOG_DEBUG);

$myContext = new \YeAPF\Connection\PersistenceContext(
    new \YeAPF\Connection\DB\RedisConnection(),
    new \YeAPF\Connection\DB\PDOConnection()
);


$myContext->getRedisConnection()->defaultExpirationTime(600);

if ($echoSteps) echo '-> ' . __LINE__ . "\n";

$myDocumentModel = new \YeAPF\ORM\DocumentModel(
    $myContext,
    'a_test'
);
$myDocumentModel->setConstraint(
    keyName: 'id',
    keyType: YeAPF_TYPE_STRING,
    length: 36,
    primary: true,
    protobufOrder: 0
);

if ($echoSteps) echo '-> ' . __LINE__ . "\n";
$myDocumentModel->setConstraint('name', YeAPF_TYPE_STRING, protobufOrder: 0);
$myDocumentModel->setConstraint('phone', YeAPF_TYPE_STRING, length: 20, acceptNULL: true, protobufOrder: 0);
$myDocumentModel->setConstraint('address', YeAPF_TYPE_STRING, length: 50, acceptNULL: true, protobufOrder: 0);
$myDocumentModel->setConstraint('country', YeAPF_TYPE_STRING, length: 20, acceptNULL: true, protobufOrder: 0);
$myDocumentModel->setConstraint('region', YeAPF_TYPE_STRING, length: 30, acceptNULL: true, protobufOrder: 0);
$myDocumentModel->setConstraint('postalZip', YeAPF_TYPE_STRING, length: 10, acceptNULL: true, protobufOrder: 0);
$myDocumentModel->setConstraint('pan', YeAPF_TYPE_STRING, length: 25, acceptNULL: true, protobufOrder: 0);
$myDocumentModel->setConstraint('cvv', YeAPF_TYPE_INT, acceptNULL: true, protobufOrder: 0);
$myDocumentModel->setConstraint('birthDate', YeAPF_TYPE_DATETIME, protobufOrder: 0, acceptNULL: true, length: 19);
$myDocumentModel->setConstraint('ageAtHire', YeAPF_TYPE_INT, protobufOrder: 0, minValue: 0);
$myDocumentModel->setConstraint('email', YeAPF_TYPE_STRING, regExpression: YeAPF_EMAIL_REGEX, protobufOrder: 0, acceptNULL: true);
$myDocumentModel->setConstraint('salary', YeAPF_TYPE_FLOAT, decimals: 2, length: 14, protobufOrder: 0);
$myDocumentModel->setConstraint('hired', YeAPF_TYPE_BOOL, protobufOrder: 0, defaultValue: false);

if ($echoSteps) echo '-> ' . __LINE__ . "\n";
$persistentTest = new YeAPF\ORM\PersistentCollection(
    $myContext,
    'a_test',
    'id',
    $myDocumentModel
);

echo "JSON structure:\n" . $persistentTest->exportDocumentModel(YeAPF_JSON_FORMAT) . "\n\n";
echo "SQL structure:\n" . $persistentTest->exportDocumentModel(YeAPF_SQL_FORMAT) . "\n\n";
echo "PROTOBUF structure:\n" . $persistentTest->exportDocumentModel(YeAPF_PROTOBUF_FORMAT) . "\n\n";

echo "Importing random data\n";
$testData = json_decode(file_get_contents(__DIR__.'/../examples/data/random-data.json'), true);
foreach ($testData as $key => $value) {
    $id=md5($value['name'].$value['email']);
    $persistentTest->setDocument($id, $value);
}


function displayResultSet($auxRet, $deleteMoreThanOne=false)
{
    global $persistentTest;

    $ret = false;
    if ($auxRet) {
        echo count($auxRet)." Record(s) found\n";
        // all the data is already in $auxRet
        // but here we want to test the redis cache
        foreach ($auxRet as $key => $value) {
            echo "ID: ".$value['id']."\n";
            $data = $persistentTest->getDocument($value['id']);
            print_r($data->exportData());
        }
        if ($deleteMoreThanOne && count($auxRet) > 1) {
            echo "More than one record found\n";
            for ($i = 1; $i < count($auxRet); $i++) {
                $idToDelete = $auxRet[$i]['id'];
                echo 'Deleting id: ' . $idToDelete . "\n";
                $persistentTest->deleteDocument($idToDelete);
            }
        }

    }
    return $ret;
}

echo "Looking a record by example\n";
$example = clone $persistentTest->getDocumentModel();
$example->name = 'John Doe';
$auxRet = $persistentTest->subsetByExample($example, 100);
if (!displayResultSet($auxRet, true)) {
    echo "Record not found\n";
    echo "Saving data into the persistent collection:\n";

    $data = clone $persistentTest->getDocumentModel();
    $data->name = 'John Doe';
    $data->ageAtHire = 25;
    $data->salary = 1000;
    $data->hired = true;
    $data->birthDate = '1980-01-01';
    $data->email = 'dFZ9W@example.com';

    $persistentTest->setDocument(null, $data);

    echo 'New id: ' . $data->id . "\n";
} else {
    $tmpClone = clone $persistentTest->getDocumentModel();
    $tmpClone->importData($auxRet[0]);
    echo "Array data cloned into a new instance of the model\n";
    print_r($tmpClone);
    $ret = true;
}

echo "\n\nLooking a record by similarity: ageAtHire > 45 and ageAtHire < 61\n";
$example = clone $persistentTest->getDocumentModel();
$example->ageAtHire = '%GT(45) and %LT(61)';
$auxRet = $persistentTest->subsetByExample($example, 100);
displayResultSet($auxRet);