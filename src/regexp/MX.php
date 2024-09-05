<?php
// MEXICO
define('YeAPF_SED_MX_IN_CURP', "'/[^A-Z0-9]//");
define('YeAPF_SED_MX_OUT_CURP', "/([A-Z]{4})(\d{6})([HM])([A-Z]{5})(\d{2})/$1$2$3$4$5/");

define('YeAPF_SED_MX_IN_RFC', "'/[^A-Z0-9]//");
define('YeAPF_SED_MX_OUT_RFC', "/([A-Z]{4})(\d{6})([A-Z0-9]{3})/$1$2$3/");

define('YeAPF_SED_MX_IN_POSTAL_CODE', "/[^0-9]//");
define('YeAPF_SED_MX_OUT_POSTAL_CODE', "/(\d{5})/$1/");
