<?php

class yAnaliserClassFoundation {
  private $identifier;
  private $float;
  private $integer;
  private $json;
  private $literal1;
  private $literal2;
  private $functionName;

  private $userFunctions;
  private $stack;

  public function __construct() {
    $this->operators  = "(>=|<=|==|>|<|\||\&)";
    $this->identifier = "([a-zA-Z_]{1}[a-zA-Z0-9_]*)";
    $this->float      = "([\-\+]{0,1}[0-9.]*)";
    $this->integer    = "([\-\+]{0,1}[0-9]*)";
    $this->json       = "(\{[a-z\'\":0-9A-Z,\ ]*\})";
    $this->literal1   = "([\']{1}.*[\']{1})";
    $this->literal2   = "([\"]{1}.*[\"]{1})";

    $this->functionName = "#($this->identifier{0,248})\(";

    $this->userFunctions = [];

    $this->stack = [];
  }

  public function __call($name, $args) {
    $args           = $args[0];
    $args['caller'] = $this;
    return $this->userFunctions[$name]->invoke(new $this->userFunctions[$name]->class, $args);
  }

  public function declareUserFunction($userFunctionName, $userFunctionBody) {
    $this->userFunctions[$userFunctionName] = $userFunctionBody;
  }

  public function undeclareUserFunction($userFunctionName) {
    if (array_key_exists($userFunctionName, $this->userFunctions)) {
      unset($this->userFunctions);
    }
  }

  function adoptClass($sourceClassNameOrClass) {
    $class   = new ReflectionClass($sourceClassNameOrClass);
    $methods = $class->getMethods();
    foreach ($methods as $methodInfo) {
      $methodName  = $methodInfo->name;
      $methodClass = $methodInfo->class;
      $this->declareUserFunction($methodName, $class->getMethod($methodName));
    }
  }

  public function getValueDefault($array, $key, $defaultValue) {
    $ret = $defaultValue;
    if (is_array($array)) {
      if (!empty($array[$key])) {
        $ret = $array[$key];
      }

    }
    return $ret;
  }

  public function getParamValue($array, $index, $defaultValue) {
    $ret = $defaultValue;
    if (is_array($array)) {
      if (!empty($array[$index])) {
        $ret = $this->getValueDefault($array[$index], 'value', $defaultValue);
      }

    }
    return $ret;
  }

  public function isInteger($strExpr) {
    preg_match_all("/$this->integer/", $strExpr, $test);
    return (count($test[0]) > 0) && ($test[0][0] == $strExpr);
  }

  public function isNumber($strExpr) {
    preg_match_all("/$this->float/", $strExpr, $test);
    return (count($test[0]) > 0) && ($test[0][0] == $strExpr);
  }

  public function isIdentifier($strExpr) {
    preg_match_all("/$this->identifier/", $strExpr, $test);
    return (count($test[0]) > 0) && ($test[0][0] == $strExpr);
  }

  public function isLiteral($strExpr) {
    preg_match_all("/$this->literal1|$this->literal2/", $strExpr, $test);
    return (count($test[0]) > 0) && ($test[0][0] == $strExpr);
  }

  private function unquote($value) {
    $firstChar = substr($value, 0, 1);
    if (substr($value, strlen($value) - 1) == $firstChar) {
      $value = substr($value, 1, strlen($value) - 2);
    }
    return $value;
  }

  private function quote($value) {
    $c1 = substr($value, 0, 1);
    if ($c1 == '"') {
      $quote = "'";
    } else {
      $quote = '"';
    }

    $value = $quote . $value . $quote;
    return $value;
  }

  private function explodeParameters($strExpr, $aValues) {
    $params = array();
    preg_match_all('/([^,]+)/', $strExpr, $output_array);
    for ($i = 0; $i < count($output_array[0]); $i++) {
      $paramItem  = trim($output_array[0][$i]);
      $paramType  = 'unknown';
      $paramValue = null;
      if ($this->isInteger($paramItem)) {
        $paramType  = "integer";
        $paramValue = $paramItem;
      } else if ($this->isNumber($paramItem)) {
        $paramType  = "float";
        $paramValue = $paramItem;
      } else if ($this->isLiteral($paramItem)) {
        $paramType  = "string";
        $paramValue = $this->unquote($paramItem);
      } else if ($this->isIdentifier($paramItem)) {
        $paramType = "identifier";
        if (is_array($aValues)) {
          if (!empty($aValues[$paramItem])) {
            $paramValue = $aValues[$paramItem];
          }
        }
      }

      $params[] = array("type" => $paramType, "value" => $paramValue);
    }
    return $params;
  }

  public function console($paramList) {
    $ret = '';
    if (is_array($paramList)) {
      for ($p = 0; $p < count($paramList); $p++) {
        $ret .= $paramList[$p]['value'] . ' ';
      }
    }
    return trim($ret);
  }

  private function _($paramList) {
    return $this->console($paramList);
  }

  function do($strExpr = null, $aValues = null) {
    $stackPtr = count($this->stack);
    if (empty($aValues) || (!is_array($aValues))) {
      if ($stackPtr > 0) {
        $aValues = $this->stack[$stackPtr];
      } else {
        $aValues = [];
      }
    } else {
      $this->stack[$stackPtr++] = $aValues;
    }
    $ret = false;
    if (is_string($strExpr)) {
      $ret = $strExpr;
      if (is_array($aValues)) {
        preg_match_all("/$this->functionName(($this->identifier|$this->operators|$this->float|$this->json|$this->literal1|$this->literal2)[,\ ]*)*\)/", $strExpr, $output_array);

        $positional = array();
        $p          = 0;
        for ($i = 0; $i < count($output_array[0]); $i++) {
          $statement    = $output_array[0][$i];
          $p            = strpos($strExpr, $statement, $p);
          $positional[] = array('statement' => $statement, 'position' => $p);
          $p++;
        }

        // die(print_r($positional));

        for ($i = 0; $i < count($positional); $i++) {
          $origem = $positional[$i]['statement'];
          preg_match_all("/($this->functionName)(.*)\)/", $origem, $funcNameList);

          $funcName   = trim($funcNameList[2][0]);
          $funcParams = $funcNameList[4][0];

          $params   = $this->explodeParameters($funcParams, $aValues);
          $funcName = "_$funcName";

          if ((method_exists($this, $funcName)) ||
            (array_key_exists($funcName, $this->userFunctions))
          ) {
            $positional[$i]['replacement'] = $this->$funcName($params);
          } else {
            $positional[$i]['replacement'] = "[ Warning: $funcName() not found! ]";
          }

        }

        // echo "<pre>\n";
        // die(print_r($positional));

        /* replacement */
        for ($i = count($positional) - 1; $i >= 0; $i--) {
          // print_r($positional[$i]);
          $p     = $positional[$i]['position'];
          $l     = strlen($positional[$i]['statement']);
          $left  = substr($strExpr, 0, $p);
          $right = substr($strExpr, $p + $l);
          // echo($left."\n==[ $p $l ]========\n".$positional[$i]['replacement']."\n==========\n".$right);
          // die(print_r($aValues));
          $strExpr = $left . $positional[$i]['replacement'] . $right;
        }
        $ret = $strExpr;

        // echo $ret."\n--FIM--\n";

      }
    }
    array_pop($this->stack);
    return $ret;
  }
}

/**
 *
 */
class yAnaliserClass extends yAnaliserClassFoundation {
  private $userFunctions;

  public function __construct() {
    parent::__construct();
    $this->userFunctions = [];
  }

  public function _decimal2($params) {
    $ret           = null;
    $value         = $this->getParamValue($params, 0, 0);
    $decimals      = $this->getParamValue($params, 1, 2);
    $dec_point     = $this->getParamValue($params, 2, '.');
    $thousands_sep = $this->getParamValue($params, 3, ',');
    if (!empty($params[0]['value'])) {
      $ret = number_format($value, $decimals, $dec_point, $thousands_sep);
    }
    return $ret;
  }

}

global $yAnaliser;
$yAnaliser = new yAnaliserClass();