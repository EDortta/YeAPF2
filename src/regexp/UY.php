<?php
// URUGUAY
define('YeAPF_SED_UY_IN_CI', "/[^0-9]//");
define('YeAPF_SED_UY_OUT_CI', "/(\d{2})(\d{5})(\d{1})/$1.$2-$3/");

define('YeAPF_SED_UY_IN_POSTAL_CODE', "/[^0-9]//");
define('YeAPF_SED_UY_OUT_POSTAL_CODE', "/(\d{5})/$1/");
