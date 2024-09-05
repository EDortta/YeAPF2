<?php
// CANADA
define('YeAPF_SED_CA_IN_POSTAL_CODE', "/[^A-Za-z0-9]//");
define('YeAPF_SED_CA_OUT_POSTAL_CODE', "/([A-Za-z]\d[A-Za-z])(\s*)(\d[A-Za-z]\d)/$1 $3/");

define('YeAPF_SED_CA_IN_SIN', "/[^0-9]//");
define('YeAPF_SED_CA_OUT_SIN', "/(\d{3})(\d{3})(\d{3})/$1 $2 $3/");

