<?php
declare(strict_types=1);
require_once __DIR__.'/../yeapf-core.php';


$myContext = new \YeAPF\Connection\PersistenceContext(
    new \YeAPF\Connection\DB\RedisConnection(),
    new \YeAPF\Connection\DB\PDOConnection()
);

echo "This script consist in a serie of raw test levels\n";
echo "-----------------------------------------------------------------------------\n";
echo "1) Using a sanitized record\n";
echo "   A sanitized record saves it strings in a way\n";
echo "   that is safe to use in a database.\n";
echo "   As it is a class, you can set values just as this:\n";
echo "      \$test = new YeAPF\ORM\SharedSanitizedRecord();\n";
echo "      \$test->aux = 'foo';\n";
echo "   In this example, 'aux' is called a 'key' and 'foo' is called a 'value'.\n";
echo "\n";

$sanitized = new YeAPF\ORM\SharedSanitizedRecord($myContext);

$aux = $sanitized->aux;
if ($aux) {
    echo "Key 'aux' already exists: $aux\nLet's delete it";
    $sanitized->delete("aux");
    echo "Deleted aux: $aux\n";
} else {
    $sanitized->aux = \YeAPF\generateUniqueId();
    echo "Let's create new key called 'aux': ";
    echo $sanitized->aux;
    echo "\n";
}

echo "-----------------------------------------------------------------------------\n";
echo "\n2) Listing the stored (key,values)\n";
echo "   Values are stored in a sanitized record\n";
echo "   using an unique key. Like a common variable.\n";
echo "   But how to list them?\n";
echo "\n";
echo "Keys:\n";
foreach($sanitized->keys() as $key) {
    echo "  $key\t";
    echo $sanitized->get($key);
    echo "\n";
}

echo "-----------------------------------------------------------------------------\n";
echo "\n3) Using HASHES\n";
echo "    For these examples, we use a json file with some random data\n";
echo "    The idea is to insert this data into the hashes and retrieve it\n";
echo "    again using the same key or a random sample of data.\n";


$collection = new \YeAPF\ORM\SharedSanitizedCollection($myContext, "test", "identifier");

$id = \YeAPF\generateUUIDv5();
echo "Working with $id\n";

$aux = $collection->hasDocument("$id");
echo "Document exists: ".($aux?"yes":"no")."\naux = ";
var_dump($aux);

if ($aux) {
    echo "Do you want to delete this? (y/n): ";
    $answer = trim(fgets(STDIN));
    if ($answer == "y") {
        echo "Deleting document $id\n";
        $delRet = $collection->deleteDocument("$id");
        var_dump($delRet);
        if ($delRet) {
            echo "Deleted document $id\n";
            $aux=false;
        }
    }
}

if (!$aux) {
    echo "Do you want to create a test document? (y/n): ";
    $answer = trim(fgets(STDIN));
    if ($answer == "y") {
        $field = [
            "document" => "123456789-0",
            "name" => "John Doe",
            "address" => "123 Main St",
            "city" => "Anytown",
            "state" => "CA",
        ];
        echo "Saving data\n";
        $collection->setDocument($id, $field);
    }
} else {
    echo "Document already exists\n";
}


echo "Retrieving data\n";
$retrievedData = $collection->getDocument($id);
print_r($retrievedData);

echo "-----------------------------------------------------------------------------\n";
echo "\n4) Listing and deleting documents\n";
$i = 0;
$list = [];
foreach($collection->listDocuments() as $id) {
    echo "$i\t";
    echo $id;
    echo "\t";
    $i++;

    $document = $collection->getDocument($id);
    echo $document["name"];
    echo "\t";
    echo $document["email"];
    echo "\n";
}

echo "Do you want to delete any?\nIf yes, please enter the id: ";
$i = intval(trim(fgets(STDIN)));
if (null!==$null && $i>=0) {
    $id = $list[$i]??null;
    echo "Deleting document $id ";
    $aux = $collection->deleteDocument($id);
    var_dump($aux);
}

echo "-----------------------------------------------------------------------------\n";
echo "\n5) Find by sample\n";
echo "   When you want to find a specific record, you can use a sample.\n";
echo "   A sample is just an array of keys.\n";
echo "     \$data = \$collection->findByExample(\$sample);\n";

$testData = json_decode(file_get_contents(__DIR__.'/random-data.json'), true);
$aRecord = $testData[mt_rand(0, count($testData)-1)];
echo "Record fo be found:\n";
print_r($aRecord);
$aKeys = array_keys($aRecord);
shuffle($aKeys);
$aKeys = array_slice($aKeys, 0, 2);
$sample = [];
echo "Keys to be used: ";
foreach($aKeys as $key) {
    echo "$key ";
    $sample[$key] = $aRecord[$key];
}
echo "\n";

$aux = $collection->findByExample($sample);
echo "Result:\n";
print_r($aux);

echo "END\n";
// foreach($testData as $key => $value) {
//     $newId = \YeAPF\generateUniqueId();
//     echo "Saving $newId\n";
//     $collection->setDocument($newId, $value);
// }