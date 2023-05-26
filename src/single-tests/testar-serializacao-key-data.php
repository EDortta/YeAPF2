<?php
declare(strict_types=1);
require_once __DIR__.'/../yeapf-core.php';


$myRecord = new YeAPF\SanitizedKeyData();

$myRecord -> name = "John Doe";
$myRecord -> birthDate = "2000-01-01";
$myRecord -> bloodType = "A";

var_dump($myRecord);