<?php
declare(strict_types=1);
require_once __DIR__.'/../yeapf-core.php';


$context = new \YeAPF\Connection\PersistenceContext(
    new \YeAPF\Connection\DB\RedisConnection(),
    new \YeAPF\Connection\DB\PDOConnection()
);

/**
 * Define a model to be used with the documents that
 * will be stored in the persistent collection.
 * @source
 */
$myDocumentModel = new \YeAPF\ORM\DocumentModel(
    $context,
    'test_collection'    
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


/**
 * Define a persistent collection
 */
$persistentTest = new YeAPF\ORM\PersistentCollection(
    $context,
    // Collection name
    "my_collection",
    // Primary key
    "id",
    // Model
    $myDocumentModel
);

/**
 * Export the model
 */
echo "\n---------------------------------------------------\n";
echo "Export the model to JSON:\n".$persistentTest->exportDocumentModel(YeAPF_JSON_FORMAT)."\n\n";
echo "\n---------------------------------------------------\n";
echo "Export the model to SQL:\n".$persistentTest->exportDocumentModel(YeAPF_SQL_FORMAT)."\n\n";
echo "\n---------------------------------------------------\n";
echo "Export the model to PROTOBUF:\n".$persistentTest->exportDocumentModel(YeAPF_PROTOBUF_FORMAT)."\n\n";

