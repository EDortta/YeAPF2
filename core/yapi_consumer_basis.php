<?php
/**
 * Project: %APP_NAME%
 * Version: %core_VERSION_SEQUENCE%
 * Date: %core_VERSION_DATE%
 * File: %FILE_NAME%
 * Last Modification: %LAST_FILE_MODIFICATION%
 * %COPYRIGHT_NOTE%
 **/

abstract class YAPIConsumer {
  private $config;
  private $debugLevel;
  private $collectDebugTrace;

  private $error_messages = [
    100 => "out_dbg is not a resource",
    101 => "Unknown REST method '%s'",
    103 => "DB error when doing this: %s . Error: %s",
    200 => "OK",
    201 => "Created",
    301 => "Moved Permanently",
    304 => "Not Modified",
    400 => "Bad Request",
    401 => "Unauthorized",
    402 => "Request Failed",
    403 => "Forbidden",
    404 => "Not Found",
    423 => "Locked",
    500 => "Internal Server Error",
    502 => "Bad Gateway",
    503 => "Empty parameter",
    504 => "Wrong parameter",
  ];

  public static function throwException($errorCode) {
    $args      = func_get_args();
    $errorCode = array_shift($args);
    if (isset($this)) {
      $msg = $this->error_messages[$errorCode];
      /* obvio que poderia ir na linha no fim do if. mas preciso depurar qual das duas está errada */
      $str = vsprintf($msg, $args);
    } else {
      $msg = "Error# $errorCode: " . str_repeat("%s ", count($args));
      /* obvio que poderia ir na linha no fim do if. mas preciso depurar qual das duas está errada */
      $str = vsprintf($msg, $args);
    }

    throw new Exception($str, 1);
  }

  private function _header_merge($a1, $a2, $unique = false) {
    if ($unique) {
      $auxRet = array();
      foreach ($a1 as $key => $value) {
        $value             = explode(":", $value);
        $auxRet[$value[0]] = trim($value[1]);
      }
      foreach ($a2 as $key => $value) {
        $value             = explode(":", $value);
        $auxRet[$value[0]] = trim($value[1]);
      }
      $ret = array();
      foreach ($auxRet as $key => $value) {
        $ret[] = "$key: $value";
      }
    } else {
      $ret = array_merge($a1, $a2);
    }
    return $ret;
  }

  public function buildReturn($http_code, $data, $debug, $error) {
    return array(
      'url'             => null,
      'http_code'       => $http_code,
      'data'            => $data,
      'dbg_preparation' => null,
      'debug'           => $debug,
      'error'           => $error,
      'headers_dbg'     => null,
      'header_size'     => 0,
      'out_dbg_name'    => null,
      'return'          => false,
    );
  }

  public function triggerError($error) {
    return $this->buildReturn(600, null, null, $error);
  }

  public function getPublicURL() {
    return $this->config['public_api_url'];
  }

  public function getPrivateURL() {
    return sprintf($this->config['private_api_url'], $this->config['marketplace_id']);
  }

  private function prepareCurlChannel($sURL, $aParameters = array(), $aHeaders = array()) {

    if ($this->debugLevel > 0) {
      if ($this->collectDebugTrace) {
        $this->out_dbg_name = tempnam("/tmp", "zcall-");
        $this->out_dbg      = fopen($this->out_dbg_name, "w");
      }
    }

    $ch = curl_init();

    if ($this->collectDebugTrace) {
      curl_setopt($ch, CURLOPT_HEADER, true);
      curl_setopt($ch, CURLOPT_VERBOSE, true);
      if (is_resource($this->out_dbg)) {
        curl_setopt($ch, CURLOPT_STDERR, $this->out_dbg);
      } else {
        $this->throwException(100);
      }

    } else {
      curl_setopt($ch, CURLOPT_VERBOSE, false);
      curl_setopt($ch, CURLOPT_HEADER, false);
    }

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_ENCODING, "");
    if (isset($this->config['proxy'])) {

      curl_setopt($ch, CURLOPT_PROXY, $this->config['proxy']['server']);
      curl_setopt($ch, CURLOPT_PROXYUSERPWD, $this->config['proxy']['userpass']);
      curl_setopt($ch, CURLOPT_PROXYPORT, $this->config['proxy']['port']);
      curl_setopt($ch, CURLOPT_PROXYTYPE, 'HTTPS');
    }

    curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 0);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);

    curl_setopt($ch, CURLOPT_FAILONERROR, false);

    curl_setopt($ch, CURLOPT_URL, "$sURL");

    $auxParameters = array_merge($this->config['headers'], $aHeaders);

    curl_setopt($ch, CURLOPT_HTTPHEADER, $auxParameters);

    $dbg_preparation = array();

    if ($this->debugLevel) {

      foreach ($auxParameters as $key => $value) {
        $dbg_preparation[count($dbg_preparation)] = "$value";
      }

    }

    return [$ch, $dbg_preparation];
  }

  private function executeCurlChannel($ch) {

    if (function_exists("_log")) {
      $executionInfo = curl_getinfo($ch);
      _log("url:", $executionInfo['url']);
    }

    $server_output = trim(curl_exec($ch));

    $debug         = "";
    $executionInfo = curl_getinfo($ch);
    $headers_dbg   = "";
    $header_size   = 0;

    if ($this->collectDebugTrace) {
      $header_size   = $executionInfo['header_size'];
      $headers_dbg   = explode("\n", substr($server_output, 0, $header_size));
      $server_output = substr($server_output, $header_size);

      if (is_resource($this->out_dbg)) {
        fclose($this->out_dbg);
        $debug = explode("\n", file_get_contents($this->out_dbg_name));
      }
    }

    $http_code  = $executionInfo['http_code'];
    $url        = $executionInfo['url'];
    $total_time = $executionInfo['total_time'];

    if (function_exists("_log")) {
      _log("http_code: $http_code");
      _log("total_time: $total_time");
    }

    curl_close($ch);

    return array(
      'http_code'    => $http_code,
      'return'       => $server_output,
      'url'          => $url,
      'headers_dbg'  => $headers_dbg,
      'header_size'  => $header_size,
      'debug'        => $debug,
      'out_dbg_name' => isset($this->out_dbg_name) ? $this->out_dbg_name : '',
    );
  }

  private function _POST_OBSOLETO($sURL, $aParameters = [], $aHeaders = []) {
    $params_string = json_encode($aParameters);
    $aux_headers   = array(
      'Content-Type: application/json',
      'Content-Length: ' . strlen($params_string));
    $aux_headers = $this->_header_merge($aux_headers, $aHeaders);

    list($ch, $dbg_preparation) = $this->prepareCurlChannel(
      $sURL,
      $aParameters,
      $aux_headers);

    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $params_string);

    $dbg_parameters = "";
    if ($this->debugLevel) {
      $dbg_parameters = json_encode($aParameters, JSON_PRETTY_PRINT) . "\n";
    }

    $tempRet                    = $this->executeCurlChannel($ch);
    $tempRet['method']          = "POST";
    $tempRet['url']             = $sURL;
    $tempRet['dbg_parameters']  = $dbg_parameters;
    $tempRet['dbg_preparation'] = $dbg_preparation;
    self::showDebug($tempRet);
    return $tempRet;
  }

  private function _POST($sURL, $aParameters = [], $aHeaders = []) {

    $paramList = array();
    $withFiles = false;
    /* determino se há um arquivo nos parâmetros */
    foreach ($aParameters as $key => $value) {

      if (!is_array($value) && is_file($value)) {
        $paramList[$key] = curl_file_create($value);
        $withFiles       = true;
      } else {
        $paramList[$key] = $value;
      }
    }
    if ($withFiles) {
      $aux_headers = array(
        'Content-Type: multipart/form-data');
    } else {
      $params_string = json_encode($aParameters);
      $aux_headers   = array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($params_string));
    }
    $aux_headers = $this->_header_merge($aux_headers, $aHeaders);

    list($ch, $dbg_preparation) = $this->prepareCurlChannel(
      $sURL,
      [],
      $aux_headers);
    if ($withFiles) {
      curl_setopt($ch, CURLOPT_POSTFIELDS, $paramList);

    } else {
      curl_setopt($ch, CURLOPT_POSTFIELDS, $params_string);

    }
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");

    $dbg_parameters = "";
    if ($this->debugLevel) {
      $dbg_parameters = json_encode($aParameters, JSON_PRETTY_PRINT) . "\n";
    }

    $tempRet                    = $this->executeCurlChannel($ch);
    $tempRet['method']          = "POST";
    $tempRet['url']             = $sURL;
    $tempRet['dbg_parameters']  = $dbg_parameters;
    $tempRet['dbg_preparation'] = $dbg_preparation;
    self::showDebug($tempRet);
    return $tempRet;
  }

  private function _PUT($sURL, $aParameters = [], $aHeaders = []) {
    $params_string = json_encode($aParameters);
    $aux_headers   = array(
      'Content-Type: application/json',
      'Content-Length: ' . strlen($params_string));
    $aux_headers                = $this->_header_merge($aux_headers, $aHeaders);
    list($ch, $dbg_preparation) = $this->prepareCurlChannel(
      $sURL,
      $aParameters,
      $aux_headers);

    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $params_string);

    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");

    $dbg_parameters = "";
    if ($this->debugLevel) {
      $dbg_parameters = json_encode($aParameters, JSON_PRETTY_PRINT) . "\n";
    }

    $tempRet                    = $this->executeCurlChannel($ch);
    $tempRet['url']             = $sURL;
    $tempRet['method']          = "PUT";
    $tempRet['dbg_preparation'] = $dbg_preparation;
    $tempRet['dbg_parameters']  = $dbg_parameters;
    self::showDebug($tempRet);
    return $tempRet;
  }

  private function _GET($sURL, $aParameters = [], $aHeaders = []) {
    $auxParameters = http_build_query($aParameters);
    if ($auxParameters > '') {
      $sURL .= "?$auxParameters";
    }
    $aux_headers = array();
    $aux_headers = $this->_header_merge($aux_headers, $aHeaders);

    list($ch, $dbg_preparation) = $this->prepareCurlChannel($sURL, [], $aux_headers);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");

    $tempRet                    = $this->executeCurlChannel($ch);
    $tempRet['method']          = "GET";
    $tempRet['url']             = $sURL;
    $tempRet['dbg_preparation'] = $dbg_preparation;
    self::showDebug($tempRet);
    return $tempRet;
  }

  private function _DELETE($sURL, $aParameters = [], $aHeaders = []) {
    $aux_headers = array();
    $aux_headers = $this->_header_merge($aux_headers, $aHeaders);

    list($ch, $dbg_preparation) = $this->prepareCurlChannel($sURL, $aParameters, $aux_headers);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");

    $tempRet                    = $this->executeCurlChannel($ch);
    $tempRet['method']          = "DELETE";
    $tempRet['url']             = $sURL;
    $tempRet['dbg_preparation'] = $dbg_preparation;
    self::showDebug($tempRet);
    return $tempRet;
  }

  private function _request($method, $url, $aParameters = [], $aHeaders = []) {
    switch ($method) {
    case "POST":
      return $this->_POST($url, $aParameters, $aHeaders);
      break;

    case "GET":
      return $this->_GET($url, $aParameters, $aHeaders);
      break;

    case "PUT":
      return $this->_PUT($url, $aParameters, $aHeaders);
      break;

    case "DELETE":
      return $this->_DELETE($url, $aParameters, $aHeaders);
      break;

    default:
      $this->throwException(101, $method);
      break;
    }
  }

  public function publicRequest($method, $cURL, $aParameters = [], $aHeaders = []) {
    $url = $this->getPublicURL() . $cURL;
    return $this->_request($method, $url, $aParameters, $aHeaders);
  }

  public function privateRequest($method, $cURL, $aParameters = [], $aHeaders = []) {
    $url = $this->getPrivateURL() . $cURL;
    return $this->_request($method, $url, $aParameters, $aHeaders);
  }

  public function showDebug($dbg) {
    if ($this->debugLevel) {

      if (function_exists("_log")) {
        $logEntry = "";
        foreach ($dbg as $key => $value) {
          if (is_array($value)) {
            $value = json_encode($value);
          }

          $logEntry .= "$key: $value\n";
        }
        _log($logEntry);

      }

    }
  }
  public function getConfig() {
    return $this->config;
  }

  public function configure(
    $JConfig,
    $cleanup = true,
    $extra_headers = false,
    $with_marketplace_id = false
  ) {
    /****************
    Recebe um JSON e um booleano
    O JConfig é um JSON para permitir uma maior flexibilidade em integrações futuras.
    Nele são esperados pelo menos os seguintes campos:
    url_api
    versao_api
    token
    marketplace_id
    Mas também podem ser usados os seguintes:
    usuario
    senha
    chave
    chave_api
    E outros que as implementações venham a precisar.

    O cleanup é um booleano que -estando em true- elimina a configuração anterior
    substituindo-a pela mesma. Se ele estiver em false, o JConfig será misturado
    à configuração existente podendo assim modificar um ou outro parâmetro da configuração
     */
    if ($cleanup) {
      $this->config = json_decode($JConfig, true);
    } else {
      $auxConfig = json_decode($JConfig, true);
      foreach ($auxConfig as $key => $value) {
        $this->config[$key] = $value;
      }
    }

    if (substr($this->config['url_api'], strlen($this->config['url_api']) - 1, 1) == '/') {
      $this->config['url_api'] = substr($this->config['url_api'], 0, strlen($this->config['url_api']) - 1);
    }

    if ($extra_headers) {

      $this->config['headers'] = array(
        'Accept: application/json',
        'Authorization: Basic ' . $this->config['token'],
      );
      $this->config['public_api_url'] = $this->config['url_api'] . "/" . $this->config['versao_api'];
      $this->config['headers']        = $extra_headers;
      if (!$with_marketplace_id) {

        $this->config['private_api_url'] = $this->config['url_api'] . "/" . $this->config['versao_api'];
      } else {
        $this->config['private_api_url'] = $this->config['url_api'] . "/" . $this->config['versao_api'] . "/marketplaces/%s";

        $this->config['headers'][] = 'Authorization: Basic ' . $this->config['token'];
      }

    } else {

      $this->config['public_api_url']  = $this->config['url_api'] . "/" . $this->config['versao_api'];
      $this->config['private_api_url'] = $this->config['url_api'] . "/" . $this->config['versao_api'] . "/marketplaces/%s";
      $this->config['headers']         = array(
        'Accept: application/json',
        'Authorization: Basic ' . $this->config['token'],
      );
    }
    //var_dump($this->config["headers"]);die();

    $this->debugLevel        = isset($this->config['debugLevel']) ? $this->config['debugLevel'] : 0;
    $this->collectDebugTrace = ($this->debugLevel > 1);
  }

}
