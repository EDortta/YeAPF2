<?php
/**
 * https://picandocodigo.github.io/ci_js/
 */
  function validateUruguayanCedula($id) {
        // remove any non-numeric characters from the ID string
    $ci = preg_replace('/\D/', '', $id);

    // make sure the cleaned ID is not empty
    if (empty(trim($ci))) {
        return false;
    }

    // get the validation digit from the end of the ID
    $validationDigit = intval(substr($ci, -1));
    $ci = substr($ci, 0, -1);

    // calculate the validation digit using the algorithm from the class
    $ci = str_pad($ci, 7, '0', STR_PAD_LEFT);
    $a = 0;
    $baseNumber = "2987634";
    for ($i = 0; $i < 7; $i++) {
        $baseDigit = intval($baseNumber[$i]);
        $ciDigit = intval($ci[$i]);
        $a += ($baseDigit * $ciDigit) % 10;
    }
    $calculatedDigit = $a % 10 == 0 ? 0 : 10 - $a % 10;

    // compare the calculated digit with the validation digit from the ID
    return $calculatedDigit == $validationDigit;
  }


$cedulas=["34889189", "2-950-098-6", "29501009", "1743550 7"];
foreach($cedulas as $cedula) {
  echo $cedula."\t ";
  if (validateUruguayanCedula($cedula)) {
    echo "Valid cedula!";
  } else {
    echo "Invalid cedula.";
  }
  echo "\n";
}
