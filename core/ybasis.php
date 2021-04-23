<?php
/**
 * yeapf basis
 *   - First Level and pure functions
 *   - Some global variables, structures and flags
 */


//--------[ uuid and tokens  ]--------

/**
 * UUIDV4 Generator
 *
 * @return     string  Returns a random UUID v4
 */
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
 * Creates a randomized token
 *
 * Randomized tokens have some prime numbers injected in order to
 * detect them as "valid" tokens
 *
 * @param      bool    $injectPrimes  TRUE indicates to inject prime numbers
 *
 * @return     string  token
 */
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

/**
 * Informs if a token is valid.
 * 
 * A token is valid when have certain primes in certain locations
 *
 * @param      string       $token  The token
 *
 * @return     bool  TRUE if the token is valid
 */
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
  $ret = _getValue($output_array[0],0,'');
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
    _dumpY(DBG_BASIS, 1, "Bearer Token enviado: [ $ret ]");
    return $ret;
  }
}

