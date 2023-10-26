<?php
declare(strict_types=1);
require_once __DIR__.'/../yeapf-core.php';

echo "---------------------------------------------------\n";
echo "Exemplo de uso do PersistentCollection\nMostra como os dados são diferentes na camada inferior\nE como não é necessário ter um modelo completo para acessar os dados\n";
$context = new \YeAPF\Connection\PersistenceContext(
    new \YeAPF\Connection\DB\RedisConnection(),
    new \YeAPF\Connection\DB\PDOConnection()
);

$translationModel = new \YeAPF\ORM\DocumentModel(
    $context,
    "translations"
);


$persistentTest = new YeAPF\ORM\PersistentCollection(
    $context,
    "translations",
    "id",
    $translationModel
);

$dados= clone $persistentTest->getDocumentModel();
$dados->id='64a6c8aa-901a-4999-9a55-61cc4835109f';
$dados->tag='inviteToRegister';
$dados->lang='en';
$dados->text="Don't have an Account";

$persistentTest->setDocument($dados->id, $dados);


$aux = $persistentTest->getDocument('64a6c8aa-901a-4999-9a55-61cc4835109f');
echo "\n---[raw data: saved in database]------------------------------------------------\n";
foreach ($aux as $key => $value) {
    echo "  [".$key."] = ".$aux->__get_raw_value($key)."\n";
}

echo "\n---[public data: as viewed by user]---------------------------------------------\n";
foreach ($aux as $key => $value) {
    echo "  [".$key."] = ".$aux->$key."\n";
}