<?php
// PARAGUAY
define('YeAPF_SED_PY_IN_CI', "/[^0-9]//");
define('YeAPF_SED_PY_OUT_CI', "/(\d{1})(\d{3})(\d{2})(\d{2})/$1.$2.$3-$4/");

define('YeAPF_SED_PY_IN_POSTAL_CODE', "/[^0-9]//");
define('YeAPF_SED_PY_OUT_POSTAL_CODE', "/(\d{4})/$1/");