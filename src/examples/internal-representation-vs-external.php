<?php
declare(strict_types=1);
require_once __DIR__.'/../yeapf-core.php';

echo "---------------------------------------------------\n";
echo "This is an example as how the strings are represented internally and\n";
echo "how they are saved in the persistent collection.\n\n";

$dado = new \YeAPF\SanitizedKeyData();
$dado -> aText = "Don't have an Account";
$dado -> aThreat = "Lets do ' OR TRUE ' a kind of sql injection";
echo "'aText' as viewed by user  : ".$dado->aText."\n";
echo "'aText' as saved internally: ".$dado->__get_raw_value('aText')."\n";
echo "\n";
echo "'aThreat' as viewed by user  : ".$dado->aThreat."\n";
echo "'aThreat' as saved internally: ".$dado->__get_raw_value('aThreat')."\n";


echo "\n";
echo "Data used by persistent collections: \n";
foreach ($dado as $key => $value) {
    echo "  [".$key."] = ".$value."\n";
}
