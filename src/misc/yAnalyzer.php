<?php
namespace YeAPF;
/**
 * Este é um extrato da função básica do YeAPF pré-processado
 * O foco desta classe é substituir ocorrencias do tipo #...(...) pelos valores correspondentes
 * Entre o '#' e o '(' pode se usar um nome de uma função implementada na classe
 * Repare que há um exemplo disso na yAnalyzerClass mais embaixo. Ela implementa uma função decimal2
 * A função do() recebe dois parâmetros: Uma string com o que se deseja processar e um vetor de contexto
 *
 *
 * Exemplo de uso:
 *   $valores = array('nome'=>'Joaquim das dores', 'email'=>'joaquim@louco.sem.br', 'divida'=>1200.45);
 *   $corpo=$yAnalyzer->do("Prezado Sr: #(nome)/#(email)<br>Sua dívida é #decimal2(divida)<hr>", $valores);
 */
class yAnalyzerClassFoundation {
  private $identifier;
  private $floatReg;
  private $integerReg;
  private $json;
  private $literal1;
  private $literal2;
  private $functionName;
  private $stack;
  private $stackPosition;
  private $operators;


  public function __construct() {
    $this->stack         = [];
    $this->stackPosition = -1;

    $this->operators  = "(>=|<=|==|>|<|\||\&)";
    $this->identifier = "([a-zA-Z_]{1}[a-zA-Z0-9_]*)";
    $this->floatReg   = "([\-\+]{0,1}[0-9.]*)";
    $this->integerReg = "([\-\+]{0,1}[0-9]*)";
    $this->json       = "(\{[a-z\'\":0-9A-Z,\ ]*\})";
    $this->literal1   = "([\']{1}.*[\']{1})";
    $this->literal2   = "([\"]{1}.*[\"]{1})";

    $this->functionName = "#($this->identifier{0,248})\(";
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
    preg_match_all("/$this->integerReg/", $strExpr, $test);
    return (count($test[0]) > 0) && ($test[0][0] == $strExpr);
  }

  public function isNumber($strExpr) {
    preg_match_all("/$this->floatReg/", $strExpr, $test);
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

  private function genError($error_code, $error_message, $error_detail = '') {
    $ret = '{ "error": "FILE NOT FOUND", "error_detail": "' . $error_detail . '"}';
    $ret = json_encode(
      [
        'error_code'    => $error_code,
        'error_message' => $error_message,
        'error_detail'  => $error_detail,
      ]
    );
    return $ret;
  }

  private function _($paramList) {
    return $this->console($paramList);
  }

  private function _coalesce($paramList) {
    $paramList = array_merge($paramList, ['type' => 'string', 'value' => '']);
    return (empty($paramList[0]['value']) ? $paramList[1]['value'] : $paramList[0]['value']);
  }

  private function _now() {
    return date('U');
  }

  private function _calcularMontoTransacao($params) {
    $debito   = $this->do('#(debito)',[],true);
    $credito   = $this->do('#(credito)',[],true);
    return number_format(($credito - $debito),2,',','');
  }

  private function _ref($params) {
    $filename   = $this->getParamValue($params, 0, "dummy.ref");
    $myBase     = dirname($_SERVER['SCRIPT_FILENAME']);
    $targetBase = substr(getcwd(), strlen($myBase));
    return "proc$targetBase/$filename";
  }

  private function _include($params) {
    $filename = $this->getParamValue($params, 0, "dummy.html");
    $ret      = $this->genError("404", "File not found", $filename . " at " . getcwd());
    if (file_exists($filename)) {
      $path_parts   = pathinfo($filename);
      $commentStart = '';
      $commentEnd   = '';
      switch ($path_parts['extension']) {
      case 'js':
      case 'css':
        $commentStart = "//------------\n// $filename start\n//------------";
        $commentEnd   = "//------------\n// $filename finish\n//------------";
        break;

      case 'html':
      case 'htm':
        $commentStart = "<!--\n   $filename start\n-->";
        $commentEnd   = "<!--\n   $filename finish\n-->";
        break;

      default:
        # code...
        break;
      }
      $content = trim(file_get_contents($filename));
      $content = preg_replace('/^[ \t]*[\n]+/m', '', $content);
      $ret     = "\n\n$commentStart\n" . $this->do($content, [], true) . "\n$commentEnd\n";
    }

    return $ret;
  }

  function do($strExpr = null, $aValues = [], $useLastStack = false) {
    if (!$useLastStack) {
      if (empty($aValues)) {
        $useLastStack = true;
      } else {
        $this->stackPosition++;
        $this->stack[$this->stackPosition] = array_merge($aValues);
      }
    } else {
      $aValues = $this->stack[$this->stackPosition];
    }
    $ret = false;
    if (is_string($strExpr)) {
      $ret = $strExpr;
      if (is_array($aValues)) {
        preg_match_all("/$this->functionName(($this->identifier|$this->operators|$this->floatReg|$this->json|$this->literal1|$this->literal2)[,\ ]*)*\)/", $strExpr, $output_array);

        $positional = array();
        $p          = 0;
        for ($i = 0; $i < count($output_array[0]); $i++) {
          $statement    = $output_array[0][$i];
          $p            = strpos($strExpr, $statement, $p);
          $positional[] = array('statement' => $statement, 'position' => $p);
          $p++;
        }

        for ($i = 0; $i < count($positional); $i++) {
          $origem = $positional[$i]['statement'];
          preg_match_all("/($this->functionName)(.*)\)/", $origem, $funcNameList);

          $funcName   = trim($funcNameList[2][0]);
          $funcParams = $funcNameList[4][0];

          $params   = $this->explodeParameters($funcParams, $aValues);
          $funcName = "_$funcName";

          if (method_exists($this, $funcName)) {
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
    if (!$useLastStack) {
      unset($this->stack[$this->stackPosition]);
      $this->stackPosition--;
    }
    return $ret;
  }
}

/**
 *
 */
class yAnalyzerClass extends yAnalyzerClassFoundation {

  public function __construct() {
    parent::__construct();
  }

  public function _cwd() {
    $dir = dirname($_SERVER['SCRIPT_FILENAME']);
    $cwd = getcwd();
    return substr($cwd, strlen($dir) + 1);
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

  public function _CNPJ($params) {
    $ret  = '';
    $cnpj = $this->getParamValue($params, 0, 0);
    $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
    // exemplo 70.106.552/0001-64
    // 70106552000164
    while (strlen($cnpj) < 14) {
      $cnpj = "0$cnpj";
    }

    $ret = substr($cnpj, 0, 2) . '.' . substr($cnpj, 2, 3) . '.' . substr($cnpj, 5, 3) . '/' . substr($cnpj, 8, 4) . '-' . substr($cnpj, 12, 2);
    return $ret;
  }

  public function _CPF($params) {
    $ret = '';
    $cpf = $this->getParamValue($params, 0, 0);
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    // exemplo 111.111.111-11
    // 11111111111
    while (strlen($cpf) < 11) {
      $cpf = "0$cpf";
    }

    $ret = substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
    return $ret;
  }

  public function _CEP($params) {
    $ret = '';
    $cep = $this->getParamValue($params, 0, 0);
    $cep = pref_replace('/[^0-9]/', '', $cep);
    // 11.111-111
    // 11111111
    while (strlen($cep) < 8) {
      $cep = "0$cep";
    }

    $ret = substr($cep, 0, 2) . '.' . substr($cep, 2, 3) . '-' . substr($cep, 5, 3);
    return $ret;
  }

}

global $yAnalyzer;
$yAnalyzer = new yAnalyzerClass();