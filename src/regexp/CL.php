<?php
// CHILE
define('YeAPF_SED_CL_IN_RUT', "'/[^0-9Kk]//");
define('YeAPF_SED_CL_OUT_RUT', "/(\d{2})(\d{3})(\d{3})([0-9Kk])/$1.$2.$3-$4/");

define('YeAPF_SED_CL_IN_POSTAL_CODE', "/[^0-9]//");
define('YeAPF_SED_CL_OUT_POSTAL_CODE', "/(\d{7})/$1/");
