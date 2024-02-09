<?php

// UNITED STATES OF AMERICA
define('YeAPF_SED_US_IN_SSN', "/[^0-9]//");
define('YeAPF_SED_US_OUT_SSN', "/(\d{3})(\d{2})(\d{4})/$1-$2-$3/");

define('YeAPF_SED_US_IN_EIN', "/[^0-9]//");
define('YeAPF_SED_US_OUT_EIN', "/(\d{3})(\d{2})(\d{4})/$1-$2-$3/");

define('YeAPF_SED_US_IN_ZIP_CODE', "/[^0-9]//");
define('YeAPF_SED_US_OUT_ZIP_CODE', "/(\d{5})(\d{4})?/$1-$2/");