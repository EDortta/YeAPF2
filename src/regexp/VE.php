<?php
// VENEZUELA
define('YeAPF_SED_VE_IN_CI', "/[^0-9]//");
define('YeAPF_SED_VE_OUT_CI', "/(\d{1})(\d{3})(\d{3})(\d{1})/$1.$2.$3-$4/");

define('YeAPF_SED_VE_IN_POSTAL_CODE', "/[^0-9]//");
define('YeAPF_SED_VE_OUT_POSTAL_CODE', "/(\d{4})/$1/");