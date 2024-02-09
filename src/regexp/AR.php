<?php

// ARGENTINA
define('YeAPF_SED_AR_IN_DNI', "/[^0-9]//");
define('YeAPF_SED_AR_OUT_DNI', "/(\d{2})(\d{3})(\d{3})/$1.$2.$3/");

define('YeAPF_SED_AR_IN_POSTAL_CODE', "/[^0-9]//");
define('YeAPF_SED_AR_OUT_POSTAL_CODE', "/(\d{4})/$1/");
