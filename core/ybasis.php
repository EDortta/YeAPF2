<?php
/**
 * yeapf basis
 *   - First Level and pure functions
 *   - Some global variables, structures and flags
 */

global $FLAGDying;
$FLAGDying = false;

global $HOST;
define("DEFAULT_HOST", "at.cli");
$HOST = (php_sapi_name() == "cli") ? DEFAULT_HOST : $_SERVER['HTTP_HOST'];

if (DEFAULT_HOST != $HOST) {
  $url_base = $_SERVER['REQUEST_SCHEME'] . "://" . $_SERVER['HTTP_HOST'] . "/" . $_SERVER['REQUEST_URI'];
} else {
  $url_base = "cli://$HOST";
}

//--------[ Errors and Exceptions ]------------
if (!function_exists("_http_response_code")) {
  function _http_response_code($code = null) {
    if ($code != null) {
      _log("http_response_code($code)");
      http_response_code($code);
    }
    return http_response_code();
  }
}

if (!function_exists("__getStack")) {
  function __getStack($trace) {
    $ret = array();
    $i   = 0;
    foreach ($trace as $key => $value) {
      $ret[$i++] = array(
        'file'     => _getValue($value, 'file'),
        'function' => _getValue($value, 'function'),
        'line'     => _getValue($value, 'line'),
      );
    }

    return $ret;
  }
}

if (!function_exists('__genDebugId')) {
  function __genDebugId() {
    global $__lastDebugId;

    if (empty($__lastDebugId)) {
      $__lastDebugId = substr(md5(gen_uuid()), 0, 15);
    }

    do {
      $y   = 2020 - date("Y");
      $m   = date("m");
      $d   = date("d");
      $h   = date("H");
      $i   = date("i");
      $s   = date("s");
      $ms  = microtime(true) - 1601673664;
      $aux = dechex($y) . dechex($m) . str_pad(dechex($d), 2, '0', STR_PAD_LEFT) . str_pad(dechex($h), 2, '0', STR_PAD_LEFT) . str_pad(dechex($i), 2, '0', STR_PAD_LEFT) . str_pad(dechex($s), 2, '0', STR_PAD_LEFT) . str_pad(dechex(intval($ms)), 3, '0', STR_PAD_LEFT);
      if ($aux == $__lastDebugId) {
        sleep(1);
      }

    } while ($aux == $__lastDebugId);

    $__lastDebugId = $aux;
    return $aux;
  }
}

if (!function_exists("_getUserAgent")) {
  function _getUserAgent() {
    $device    = null;
    $userAgent = mb_strtolower(!empty($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : "CLI");
    $search    = array("ipad", "iphone", "blackberry", "android", "ios", "postmanruntime");
    foreach ($search as $key) {
      if (stristr($userAgent, $key)) {
        $device = $key;
      }
    }
    _log("DEVICE: " . $userAgent . " is '$device'");

    return $device;
  }
}

if (!function_exists("__outputIsJson")) {
  function __outputIsJson() {
    $ret = false;
    $ret = (
      isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
      strcasecmp($_SERVER['HTTP_X_REQUESTED_WITH'], 'xmlhttprequest') == 0
    );
    foreach (headers_list() as $value) {
      if (false !== strpos($value, "application/json")) {
        $ret = true;
      }
      if (false !== strpos($value, "multipart/form-data")) {
        $ret = true;
      }
    }
    return $ret;
  }
}

/**
 * Global response mechanism
 */
if (!function_exists("_response")) {
  function _response($response) {
    if (function_exists("getallheaders")) {
      $headers = getallheaders();
    } else {
      $headers = array('Content-Type' => 'text');
    }

    if (is_array($response)) {
      echo json_encode($response, JSON_PRETTY_PRINT);
    } else {
      echo json_encode(['status' => $response], JSON_PRETTY_PRINT);
    }
    exit;
  }
}

global $auxDebugId;
function __defaultErrorHandler($errno, $errstr, $errfile, $errline) {
  global $FLAGDying, $auxDebugId;
  if (!$FLAGDying) {
    $FLAGDying = true;
    if (ob_get_contents()) {
      ob_end_clean();
    }

    $ret = array(
      "errtype"   => "error",
      "errno"     => $errno,
      "errstr"    => $errstr,
      "errfile"   => $errfile,
      "errline"   => $errline,
      "useragent" => _getUserAgent(),
    );

    $ret['errstr'] = str_replace("\n", " ", $ret['errstr']);
    $ret['errstr'] = preg_replace('/\s+/', " ", $ret['errstr']);

    _logError($ret);
    $ret['call_stack'] = __getStack(debug_backtrace());

    _log(_getCallStack());
    if ($GLOBALS['CFGOcultarErros']) {
      $auxDebugId = __genDebugId();
      $filename   = dirname($GLOBALS['CFGLogFilename']) . "/$auxDebugId.json";
      file_put_contents($filename, json_encode($ret, JSON_PRETTY_PRINT));
      if (__outputIsJson()) {
        _http_response_code(500);
        unset($ret['call_stack']);
        _response($ret);
      } else {
        if ((php_sapi_name() == "cli")) {
          echo "\n**** Error $auxDebugId\n";
        } else {
          $auxURL = $GLOBALS['url_base'] . "500.html?$auxDebugId";
          header("Location: $auxURL");
        }
      }
    } else {
      if (!__outputIsJson()) {
        echo "<pre>";
      }

      _http_response_code(500);
      _response($ret);

    }

    die();
  }
}

set_error_handler("__defaultErrorHandler");

function __defaultExceptionHandler($exception) {
  global $FLAGDying, $auxDebugId;

  if (!$FLAGDying) {
    $FLAGDying = true;
    if (ob_get_contents()) {
      ob_end_clean();
    }

    $ret = array(
      "errtype"   => "exception",
      "errstr"    => $exception->getMessage(),
      "errfile"   => $exception->getFile(),
      "errline"   => $exception->getLine(),
      "useragent" => _getUserAgent(),
    );

    $ret['errstr'] = str_replace("\n", " ", $ret['errstr']);
    $ret['errstr'] = preg_replace('/\s+/', " ", $ret['errstr']);

    _logError($ret);
    $ret['call_stack'] = __getStack($exception->getTrace());

    _log(_getCallStack());
    if ($GLOBALS['CFGOcultarErros']) {
      $auxDebugId = __genDebugId();
      $filename   = dirname($GLOBALS['CFGLogFilename']) . "/$auxDebugId.json";
      file_put_contents($filename, json_encode($ret, JSON_PRETTY_PRINT));
      if (__outputIsJson()) {
        _http_response_code(500);
        unset($ret['call_stack']);
        _response($ret);
      } else {
        if ((php_sapi_name() == "cli")) {
          echo "\n**** Error $auxDebugId\n";
        } else {
          $auxURL = $GLOBALS['url_base'] . "500.html?$auxDebugId";
          header("Location: $auxURL");
        }
      }
    } else {
      if (!__outputIsJson()) {
        echo "<pre>";
      }

      _http_response_code(500);
      _response($ret);

    }

    die();
  }
}

set_exception_handler("__defaultExceptionHandler");

function __defaultShutdownFunction() {
  global $FLAGDying;
  if (!$FLAGDying) {
    $err = error_get_last();
    if (null != $err) {
      _http_response_code(500);
      if (!__outputIsJson()) {
        echo "<pre>";
      }
      _response($err);
      if (!__outputIsJson()) {
        echo "</pre>";
      }
    }
  }
}

register_shutdown_function("__defaultShutdownFunction");

//--------[ uuid and tokens  ]--------

/* gerador de UUID v4 */
function gen_uuid() {
  /* ver https://www.php.net/manual/en/function.uniqid.php#94959 */
  return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
    random_int(0, 0xffff), random_int(0, 0xffff),
    random_int(0, 0xffff),
    random_int(0, 0x0fff) | 0x4000,
    random_int(0, 0x3fff) | 0x8000,
    random_int(0, 0xffff), random_int(0, 0xffff), random_int(0, 0xffff)
  );
}

/**
 * Gera um token para ser usado na autenticação
 **/
function __genRandomToken($injectPrimes = false) {
  if (function_exists("openssl_random_pseudo_bytes")) {
    $token = openssl_random_pseudo_bytes(16);
  } else {
    $token = random_bytes(16);
  }
  $token = bin2hex($token);

  if ($injectPrimes) {
    /**
     * Como passo intermediario até implementarmos o cadastro completo pela API
     * estamos hackeando o token injetando três primos em sequencia nas posições
     * 1, 7, 11 e 13
     * Veja __validateRandomToken()
     **/
    $primes = [11, 3, 47, 5, 43, 59, 2, 23, 29, 97,
      13, 41, 79, 89, 19, 73, 31, 17, 53, 61, 71,
      83, 37, 7, 67];
    $randFlag = mt_rand(0, count($primes) - 4);
    $map      = [1, 7, 11, 13];
    for ($i = 0; $i < 3; $i++) {
      $aux = $primes[$randFlag + $i];
      if ($aux < 10) {
        $aux = chr(mt_rand(97, 102)) . $aux;
      }

      $token = substr($token, 0, $map[$i]) . $aux . substr($token, $map[$i] + 2);
    }
  }
  return $token;
}

function __validateRandomToken($token) {
  $ret = false;
  /**
   * tokens temorários gerados no voo
   * servem apenas para que o cadastro possa pular pela API
   * Esses tokens são gerados por __genRandomToken()
   **/
  $primes = [11, 3, 47, 5, 43, 59, 2, 23, 29, 97, 13, 41, 79, 89, 19, 73, 31, 17, 53, 61, 71, 83, 37, 7, 67];
  $map    = [1, 7, 11, 13];
  $first  = substr($token, $map[0], 2);
  $first  = preg_replace('/[^0-9]+/', '', $first);
  if (in_array($first, $primes)) {
    $randFlag = array_search($first, $primes);
    $ret      = array(
      'success'    => true,
      'token_type' => 'token',
    );
    for ($i = 1; $i < 3; $i++) {
      $aux = substr($token, $map[$i], 2);
      $aux = preg_replace('/[^0-9]+/', '', $aux);
      $aux = intval($aux);
      if ($aux != $primes[$randFlag + $i]) {
        $ret = false;
      }
    }
  }
}

//--------[ URL/Client interaction ]--------

/**
 * Given a complete URL, it returns the domain
 */
function _getDomainFromURL($url = '') {
  if (empty($url)) {
    $url = $GLOBALS['CFGSiteURL'];
  }

  preg_match_all('/[a-zA-Z0-9]*\.([a-zA-Z0-9\.]*)/', $url, $output_array);
  $ret = $output_array[0][0];
  $ret = str_replace("www.", "", $ret);
  return $ret;
}

if (!function_exists("_getRealIpAddr")) {
  function _getRealIpAddr() {
    $ipaddress = 'UNKNOWN';
    $keys      = array('HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR', 'HTTP_CLIENT_IP');
    foreach ($keys as $k) {
      if (isset($_SERVER[$k]) && !empty($_SERVER[$k]) && filter_var($_SERVER[$k], FILTER_VALIDATE_IP)) {
        $ipaddress = $_SERVER[$k];
        break;
      }
    }
    return $ipaddress;
  }
}

function _response_into_range($code, $min, $max) {
  return $code >= $min && $code <= $max;
}


if (!function_exists("getAuthorizationHeader")) {
  function getAuthorizationHeader() {
    $headers = null;
    if (isset($_SERVER['Authorization'])) {
      $headers = trim($_SERVER["Authorization"]);
    } else if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
      $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
    } elseif (function_exists('getallheaders')) {
      $requestHeaders = getallheaders();
      $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
      if (isset($requestHeaders['Authorization'])) {
        $headers = trim($requestHeaders['Authorization']);
      }
    }
    return $headers;
  }
}

if (!function_exists("getBearerToken")) {
  function getBearerToken() {
    $headers = getAuthorizationHeader();
    $ret     = null;
    if (!empty($headers)) {
      if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
        $ret = $matches[1];
      }
    }
    _log("Bearer Token enviado: [ $ret ]");
    return $ret;
  }
}

