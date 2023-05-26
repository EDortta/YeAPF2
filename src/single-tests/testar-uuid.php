<?php
declare(strict_types=1);
require_once __DIR__.'/../yeapf-core.php';


echo "\n----\n";
// print_r(\YeAPF\YeAPFConfig::getSection("randomness")->namespace);
// echo "\n----\n";

$namespace = '6ba7b810-9dad-01d1-80b4-00c04fd430c8';
$name = 'example';
$serverId = '1234';

echo "UUIDv5\n";
$uuid = \YeAPF\generateUUIDv5();
echo "$uuid\n";

echo "UUIDv4\n";
echo \YeAPF\generateUUIDv4()."\n";


echo "UniqueId\n";
echo \YeAPF\generateUniqueId()."\n";

exit;