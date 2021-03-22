<?php

$libs = ['ydbskeleton.php'];
$libFolder = dirname(__FILE__);
foreach ($libs as $libName) {
  $_libName = "$libFolder/$libName";
  if (file_exists($_libName)) {
    ((@include_once "$_libName") || die("Error loading $_libName"));
  } else {
    die("$_libName not found");
  }
}


