<?php
// BRAZIL
define('YeAPF_SED_BR_IN_CNPJ', "/[^0-9]//");
define('YeAPF_SED_BR_OUT_CNPJ', "/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/$1.$2.$3\/$4-$5/");

define('YeAPF_SED_BR_IN_CPF', "/[^0-9]//");
define('YeAPF_SED_BR_OUT_CPF', "/(\d{3})(\d{3})(\d{3})(\d{2})/$1.$2.$3-$4/");

define('YeAPF_SED_BR_IN_POSTAL_CODE', "/[^0-9]//");
define('YeAPF_SED_BR_OUT_POSTAL_CODE', "/(\d{5})(\d{3})/$1-$2/");

