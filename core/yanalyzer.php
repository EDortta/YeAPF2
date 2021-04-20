<?php

/**
 * Analyser Class Foundation
 *
 * This classes is the foundation over which the yAnalyzer will be
 * built.
 * It recognizes the well known YeAPF pre-processor syntax of
 * <#>[method]<(>[parameters]<)> or #func() simplified.
 * Is has some methods that allows the programmer to inject some
 * functions that will be seen as a method that in turn, could be
 * treated as an extension of the basic analyzer class.
 */
class yAnalyzerClassFoundation {
  /**
   * RegExp that helps to detect an identifier
   */
  private $identifier;
  /**
   * RegExp that helps to identify a float in an string
   */
  private $float;
  /**
   * RegExp that helps to identify an integer into a string
   */
  private $integer;
  /**
   * RegExp that helps to detect a JSON string into a string
   */
  private $json;
  /**
   * RegExp that helps to identify a literar into a string
   */
  private $literal1;
  /**
   * RegExp that helps to identify a literar into a string
   */
  private $literal2;
  /**
   * RegExp that helps to identify a function name into a string
   */
  private $functionName;

  /**
   * Associative array containing all the user functions.
   */
  private $userFunctions;
  /**
   * Analyzer FILO stack
   */
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

  /**
   * Add a function into the analyzer set that can be called later
   * using the #func() syntax.
   * A function can be remover using undeclareUserFunction().
   *
   * @param      string  $userFunctionName  The user function name.
   *                                        In order to be called by
   *                                        the analyzer when #func()
   *                                        is found, it need to
   *                                        start with '_'
   * @param      function  $userFunctionBody  The user function body
   *
   * @return     boolean TRUE if the function does not exists.
   *                     FALSE if the function already exists as no
   *                     function cannot be replaced.
   */
  public function declareUserFunction($userFunctionName, $userFunctionBody) {
    $ret = false;
    if (empty($this->userFunctions[$userFunctionName])) {
      $this->userFunctions[$userFunctionName] = $userFunctionBody;
      $ret = true;
    }
    return $ret;
  }

  /**
   * It is the opposite to declareUserFunction()
   * It can be used to removes a function previously injected
   * into the analyzer.
   *
   * @param      string  $userFunctionName  The user function name
   */
  public function undeclareUserFunction($userFunctionName) {
    if (array_key_exists($userFunctionName, $this->userFunctions)) {
      unset($this->userFunctions);
    }
  }

  /**
   * Injects all the method of a class into the analyzer
   *
   * @param      StringOrClass  $sourceClassNameOrClass  The name of
   *                                          the class or the class
   *                                          itself that will be
   *                                          adopted into the analyzer
   */
  function adoptClass($sourceClassNameOrClass) {
    $class   = new ReflectionClass($sourceClassNameOrClass);
    $methods = $class->getMethods();
    foreach ($methods as $methodInfo) {
      $methodName  = $methodInfo->name;
      $methodClass = $methodInfo->class;
      $this->declareUserFunction($methodName, $class->getMethod($methodName));
    }
  }

  /**
   * Gets the value default of an array item.
   *
   * @param      array   $array         The array
   * @param      mixed   $key           The array key
   * @param      mixed   $defaultValue  The default value when the key
   *                                    is not present in the array.
   *
   * @return     mixed  The item value or the indicated default value.
   */
  public function getValueDefault($array, $key, $defaultValue) {
    $ret = $defaultValue;
    if (is_array($array)) {
      if (!empty($array[$key])) {
        $ret = $array[$key];
      }

    }
    return $ret;
  }

  /**
   * Gets the parameter value by it index.
   *
   * When a function is called from the html file in the #func() format
   * the parameters passed to the function are placed into a stack and
   * then passed to the function.
   * So this method is used by those function impelentations to get
   * the parameter value by order.
   *
   * @param      array    $array         The parameters array
   * @param      integer  $index         The index
   * @param      mixed    $defaultValue  The default value
   *
   * @return     Mixed  The parameter value.
   */
  public function getParamValue($array, $index, $defaultValue) {
    $ret = $defaultValue;
    if (is_array($array)) {
      if (!empty($array[$index])) {
        $ret = $this->getValueDefault($array[$index], 'value', $defaultValue);
      }

    }
    return $ret;
  }

  /**
   * Determines whether the specified string expression is integer.
   *
   * @param      string  $strExpr  The string expression
   *
   * @return     bool    True if the specified string expression is integer, False otherwise.
   */
  public function isInteger($strExpr) {
    preg_match_all("/$this->integer/", $strExpr, $test);
    return (count($test[0]) > 0) && ($test[0][0] == $strExpr);
  }

  /**
   * Determines whether the specified string expression is number.
   *
   * @param      string  $strExpr  The string expression
   *
   * @return     bool    True if the specified string expression is number, False otherwise.
   */
  public function isNumber($strExpr) {
    preg_match_all("/$this->float/", $strExpr, $test);
    return (count($test[0]) > 0) && ($test[0][0] == $strExpr);
  }

  /**
   * Determines whether the specified string expression is identifier.
   *
   * @param      string  $strExpr  The string expression
   *
   * @return     bool    True if the specified string expression is identifier, False otherwise.
   */
  public function isIdentifier($strExpr) {
    preg_match_all("/$this->identifier/", $strExpr, $test);
    return (count($test[0]) > 0) && ($test[0][0] == $strExpr);
  }

  /**
   * Determines whether the specified string expression is literal.
   *
   * @param      string  $strExpr  The string expression
   *
   * @return     bool    True if the specified string expression is literal, False otherwise.
   */
  public function isLiteral($strExpr) {
    preg_match_all("/$this->literal1|$this->literal2/", $strExpr, $test);
    return (count($test[0]) > 0) && ($test[0][0] == $strExpr);
  }

  /**
   * Removes the quotes of an string and unslash it.
   *
   * @param      string  $value  The quoted string
   *
   * @return     string  The unquoted string
   */
  private function unquote($value) {
    $firstChar = substr($value, 0, 1);
    if (substr($value, strlen($value) - 1) == $firstChar) {
      $value = substr($value, 1, strlen($value) - 2);
    }
    return stripslashes($value);
  }

  /**
   * Quote an string slashing it contents.
   *
   * @param      string  $value  The string to be quoted
   *
   * @return     string  The quoted string.
   */
  private function quote($value) {
    $c1 = substr($value, 0, 1);
    $value=unquote($value);
    if ($c1 == '"') {
      $quote = "'";
    } else {
      $quote = '"';
    }

    $value = $quote . addslashes($value) . $quote;
    return $value;
  }

  /**
   * Convert a comma separated parameters name into appropriate values
   *
   * @param      string  $strExpr  Comma separated string
   * @param      array   $aValues  An associative array of values.
   *                               If the parameter is not a constant,
   *                               then this function tries to get the
   *                               value from the array. The default
   *                               values for parameters is null.
   *
   * @return     array  Associative array with type an value of
   *                    parameters.
   */
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

  /**
   * Places all the passed parameters in a string.
   *
   * @param      array  $paramList  The parameter list
   *
   * @return     string  All the parameters separated by spaces.
   */
  public function console($paramList) {
    $ret = '';
    if (is_array($paramList)) {
      for ($p = 0; $p < count($paramList); $p++) {
        $ret .= $paramList[$p]['value'] . ' ';
      }
    }
    return trim($ret);
  }

  /**
   * Basic YeAPF macro-function #()
   *
   * It just return the values passed in the parameters.
   * Usually it is used to display contextual values but it
   * can be used to show constants.
   *
   * @param      array  $paramList  The parameter list
   *
   * @return     string  A space separated string within all the params
   */
  private function _($paramList) {
    return $this->console($paramList);
  }

  /**
   * The main entry point of the Analyzer.
   *
   * It take two parameters: an expression and a set of values. All
   * the expression will be evaluated and solved using this contextual
   * set of values.
   *
   * @param    string $strExpr The string to be analyzed
   * @param    array  $aValues The context
   *
   * @return  string  The processed string
   */
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
            if ($funcName=='d_') {
              print_r($this);
              die("Calling $funcName(".json_encode($params).")");
            }
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
 * yAnalyzerClass is an implementation of yAnalyzerClassFoundation.
 * It was thought to contain some more deep functions that will be
 * implemented later.
 */
class yAnalyzerClass extends yAnalyzerClassFoundation {
  private $userFunctions;

  public function __construct() {
    parent::__construct();
    $this->userFunctions = [];
  }

  public function _coalesce($paramList) {
    $paramList = array_merge($paramList, ['type' => 'string', 'value' => '']);
    return (empty($paramList[0]['value']) ? $paramList[1]['value'] : $paramList[0]['value']);
  }

  public function _ref($params) {
    $filename = $this->getParamValue($params, 0, "dummy.ref");
    $myBase = dirname($_SERVER['SCRIPT_FILENAME']);
    $targetBase = substr(getcwd(),strlen($myBase));
    return "proc$targetBase/$filename";
  }

  public function _cwd() {
    $dir = dirname($_SERVER['SCRIPT_FILENAME']);
    $cwd = getcwd();
    return substr($cwd, strlen($dir) + 1);
  }

  /**
   * Format a decimal value in order to be displayed to the user.
   *
   * @param      array  $params  A YeAPF parameters structure
   *                             0 is the value
   *                             1 is the number of decimals (defaults to 2)
   *                             2 the decimal separator (def '.')
   *                             3 the thousand separator (def ',')
   *
   * @return     string  A formatted decimal value
   */
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

global $yAnalyzer;
$yAnalyzer = new yAnalyzerClass();
