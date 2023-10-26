<?php

class CustomerDocumentChecker extends \YeAPF\Plugins\ServicePlugin implements \YeAPF\Plugins\ServicePluginInterface
{
  function __construct() {
    parent::__construct(__FILE__);
  }

  private function check_id_UY($id, $documentType = null) {
    // remove any non-numeric characters from the ID string
    $ci = preg_replace('/\D/', '', $id);

    // make sure the cleaned ID is not empty
    if (empty(trim($ci))) {
      return false;
    }

    // get the validation digit from the end of the ID
    $validationDigit = intval(substr($ci, -1));
    $ci              = substr($ci, 0, -1);

    // calculate the validation digit using the algorithm from the class
    $ci         = str_pad($ci, 7, '0', STR_PAD_LEFT);
    $a          = 0;
    $baseNumber = "2987634";
    for ($i = 0; $i < 7; $i++) {
      $baseDigit = intval($baseNumber[$i]);
      $ciDigit   = intval($ci[$i]);
      $a += ($baseDigit * $ciDigit) % 10;
    }
    $calculatedDigit = $a % 10 == 0 ? 0 : 10 - $a % 10;

    // compare the calculated digit with the validation digit from the ID
    return $calculatedDigit == $validationDigit;
  }

  private function check_id_AR($id, $documentType = null) {
    // Remove any non-digit characters
    $id = preg_replace('/\D/', '', $id);

    // Check length
    if (strlen($id) !== 8) {
      return false;
    }

    // Check that first digit is not 0
    if (substr($id, 0, 1) === '0') {
      return false;
    }

    // Calculate the verification digit
    $sum = 0;
    for ($i = 0; $i < 7; $i++) {
      $sum += ($id[$i] * (2 + ($i % 6)));
    }
    $verification_digit = (11 - ($sum % 11)) % 11;

    // Check the verification digit against the last digit of the input
    return ($id[7] == $verification_digit);
  }

  private function check_id_PE($id, $documentType = null) {
    $checkDNI = function ($id) {
      $dni = strtoupper(trim(str_replace('-', '', $id)));
      if (empty($dni) || strlen($dni) < 9) {
        return false;
      }
      $multiples = array(3, 2, 7, 6, 5, 4, 3, 2);
      $dcontrols = array(
        'numbers' => array(6, 7, 8, 9, 0, 1, 1, 2, 3, 4, 5),
        'letters' => array('K', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'),
      );
      $numdni   = str_split(substr($dni, 0, -1));
      $dcontrol = substr($dni, -1);
      $dsum     = array_reduce($numdni, function ($acc, $digit) use ($multiples) {
        $acc += $digit * array_shift($multiples);
        return $acc;
      }, 0);
      $key   = 11 - ($dsum % 11);
      $index = ($key === 11) ? 0 : $key;
      if (is_numeric($dni)) {
        return $dcontrols['numbers'][$index] === intval($dcontrol);
      }
      return $dcontrols['letters'][$index] === $dcontrol;
    };

    return $checkDNI($id);
  }

  public function validate($ISOCountryCode, $id, $documentType = null) {
    $ret    = false;
    $fnName = 'check_id_' . $ISOCountryCode;
    if (method_exists($this, $fnName)) {
      $ret = $this->{$fnName}($id);
    }

    return $ret;
  }

  public function registerServiceMethods($server) {

  }
}

global $customerDocumentChecker;
$customerDocumentChecker = new CustomerDocumentChecker();
