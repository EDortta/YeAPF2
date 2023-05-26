<?php
declare(strict_types=1);
require_once __DIR__.'/../yeapf-core.php';

echo "---------------------------------------------------\n";
echo "Exemplo de uso do PersistentCollection, DocumentModel e do SanitizedKeyData\n\n";
$context = new \YeAPF\Connection\PersistenceContext(
    new \YeAPF\Connection\DB\RedisConnection(),
    new \YeAPF\Connection\DB\PDOConnection()
);

$translationModel = new \YeAPF\ORM\DocumentModel(
    $context,
    "my_collection"
);

if ($translationModel->assetsFolderModelExists()) {
    echo "Assets folder already exists\n";
    $translationModel->importModelFromAssetFolder();
} else {
    echo "Importing model from DB: \n";
    $translationModel->importModelFromDB();
}

echo "Exporting to assets folder: ".($translationModel->exportModelToAssetFolder()==true?"OK\n":"ERROR\n");


$persistentTest = new YeAPF\ORM\PersistentCollection(
    $context,
    // Collection name
    "my_collection",
    // Primary key
    "id",
    // Model
    $translationModel
);

