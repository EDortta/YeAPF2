<?php
/**
 * Project: %APP_NAME%
 * Version: %core_VERSION_SEQUENCE%
 * Date: %core_VERSION_DATE%
 * File: %FILE_NAME%
 * Last Modification: %LAST_FILE_MODIFICATION%
 * %COPYRIGHT_NOTE%
 **/

/**
 * Conjunto de funções de primeiro nível que ajudam
 * todas as partes do sistema: lib, api, web
 **/
global $dbConn;
global $dbConfig;
global $CFGServer;
global $CFGApp;
global $auxDebugErroDesc;
global $__FIRST_LOG_ENTRY;

$CFGServer         = ['configured' => false];
$CFGApp            = ['configured' => false];
$__FIRST_LOG_ENTRY = true;

//--------[ low level ]--------

/**
 * Voids the error of request by an inexistent array value
 */
function _getValue($array, $key, $default = null) {
  if (!empty($array)) {
    if (isset($array[$key])) {
      return $array[$key];
    } else {
      return $default;
    }
  } else {
    return $default;
  }
}

function _extractSimilarValues($array, $prefix) {
  $ret = [];
  foreach ($array as $key => $value) {
    if (substr($key, 0, strlen($prefix)) == $prefix) {
      $ret[$key] = $value;
    }
  }
  return $ret;
}

function __removeLastSlash($str) {
  $str = trim($str);
  if (substr($str, -1) == '/') {
    $str = substr($str, 0, -1);
  }
  return $str;
}

//--------[ log/debug functions ]--------
if (!function_exists("_infoDbg_")) {
  function _infoDbg_($dbg, $dbgNdx) {
    global $HOST;

    if (isset($dbg[$dbgNdx])) {
      if ($HOST == DEFAULT_HOST) {
        $_file = basename(_getValue($dbg[$dbgNdx], 'file'));
      } else {
        $_file = $_SERVER['SCRIPT_NAME'];
      }

      return $_file . ":" . _getValue($dbg[$dbgNdx], 'line') . "@" . _getValue($dbg[$dbgNdx], 'function') . "() ";
    } else {
      return "";
    }

  }
}

if (!function_exists("_getCallStack")) {
  function _getCallStack($dbg = null) {
    if ($dbg == null) {
      $dbg = debug_backtrace();
    }
    $logEntry = "\n         CALL STACK\n";
    for ($i = 0; $i < count($dbg); $i++) {
      $logEntry .= "         " . _infoDbg_($dbg, $i) . "\n";
    }
    $logEntry .= "\n---END----\n\n";
    return $logEntry;
  }
}

if (!function_exists("_log")) {
  global $_tempLogString;
  $_tempLogString = '';

  function _log() {
    global $__FIRST_LOG_ENTRY, $CFGLogFilename, $CFGLogLevel, $_tempLogString;
    // $_SERVER['SCRIPT_FILENAME']
    $dbg    = debug_backtrace();
    $dbgNdx = 0;
    while (($dbgNdx < count($dbg) - 1) && (strpos('*:_log:logReturn:', ':' . $dbg[$dbgNdx]['function'] . ':') > 0)) {
      $dbgNdx++;
    }

    $args = func_get_args();

    if (true) {
      $firstLine = "";
      $dbgNdx++;
    } else {
      $firstLine = _infoDbg_($dbg, $dbgNdx + 1);
      if ($firstLine > "") {
        $firstLine .= "\n           \--> ";
      }
    }

    $logHeader = date("H:i:s ");
    $logMargin = str_repeat(" ", strlen($logHeader));

    $logEntry = $logHeader . $firstLine . _infoDbg_($dbg, $dbgNdx);
    foreach ($args as $value) {
      if ($value != "require") {
        if (is_array($value)) {
          $logEntry .= json_encode($value, JSON_PRETTY_PRINT);
        } else {
          $logEntry .= "$value ";
        }

      }
    }

    $logEntry = str_replace("\n", "\n" . $logMargin, $logEntry);
    $_tempLogString .= $logEntry . "\n";
    if (strlen($_tempLogString) > 4096) {
      $_tempLogString = substr($_tempLogString, strpos($_tempLogString, "\n") + 1);
    }

    if ($CFGLogLevel > 0) {

      if (is_writable(dirname($CFGLogFilename))) {

        if ((file_exists($CFGLogFilename)) && (filesize($CFGLogFilename) > 1024 * 1024)) {
          rename($CFGLogFilename, $CFGLogFilename . "." . date('Y-m-dTH-m'));
          touch($CFGLogFilename);
        }

        $logFile = fopen($CFGLogFilename, "a");
        if ($logFile) {
          if ($__FIRST_LOG_ENTRY) {
            $logEntry          = "\n--------------------------------------------\n";
            $__FIRST_LOG_ENTRY = false;
          }
          fwrite($logFile, trim($logEntry) . "\n");
          fclose($logFile);
        }
      } else {
        _die("$CFGLogFilename cannot be written");
      }
    } else {
      syslog(LOG_INFO, trim($logEntry));
    }
  }

  function _dumpY($logFlag, $level) {
    global $yeapfLogFlags, $yeapfLogLevel;

    if (empty($yeapfLogFlags)) {
      $yeapfLogFlags = 65535;
    }

    if (empty($yeapfLogLevel)) {
      $yeapfLogLevel = 99;
    }

    if ($level <= $yeapfLogLevel) {
      if (($logFlag & $yeapfLogFlags) > 0) {
        $paramNdx = 0;
        $args     = func_get_args();
        $argList  = '';
        foreach ($args as $a) {
          $paramNdx++;
          if ($paramNdx > 2) {
            if ($argList > '') {
              $argList .= ' ';
            }

            $argList .= $a;
          }
        }
        _log("$argList");
      }
    }
  }

  function _emptyRet() {
    return [
      'record'   => [],
      'analised' => [],
      'ret_code' => 0,
    ];
  }

  function _record(&$ret) {
    $args     = func_get_args();
    $logEntry = "";
    $ndx      = 1;
    foreach ($args as $value) {
      if ($ndx > 1) {
        if ($value != "require") {
          if (is_array($value)) {
            $logEntry .= json_encode($value, JSON_PRETTY_PRINT);
          } else {
            $logEntry .= "$value ";
          }

        }
      }
      $ndx++;
    }
    _log($logEntry);
    if (empty($ret['record'])) {
      $ret['record'] = [];
    }

    $ret['record'][] = $logEntry;
  }

  function _mergeRecord(&$targetRet, $sourceRet) {
    $targetRet['record']   = array_merge($targetRet['record'], $sourceRet['record']);
    $targetRet['analised'] = array_merge($targetRet['analised'], $sourceRet['analised']);
  }
}

if (!function_exists("_logError")) {
  function _logError($aError, $showEmpty = true) {
    if (!empty($aError)) {
      $errLine = "\n          EXECUTION ERROR!\n          ";
      foreach ($aError as $key => $value) {
        if (!empty($value)) {
          if (!is_array($value)) {
            $errLine .= "$key: $value\n          ";
          } else {
            $errLine .= "$key: array()\n          ";
          }
        }
      }
      _log($errLine);
      _log(_getCallStack());
    }
  }
}

if (!function_exists("_die")) {
  function _die() {
    global $FLAGDying, $auxDebugId;

    if (!$FLAGDying) {
      $FLAGDying = true;
      if (ob_get_contents()) {
        ob_end_clean();
      }
      
      $args  = func_get_args();
      $args2 = array("message" => implode(". ", $args));
      if (class_exists("DBConnector")) {
        $args2['db_error'] = DBConnector::getLastError();
      }

      call_user_func_array('_log', $args2);
      _response($args2);
      die();
    }
  }
}

$auxDebugErroDesc = '';

function __definirURL($url_app, $uri_base = '', $forcar_https = null) {
  global $CFGServer;
  if ($uri_base == '') {
    $uri_base = _getValue($GLOBALS, '__uri_base', '/');
  } else {
    $GLOBALS['__uri_base'] = $uri_base;
  }

  if ($forcar_https == null) {
    $forcar_https = _getValue($GLOBALS, '__forcar_https', false);
  } else {
    $GLOBALS['__forcar_https'] = $forcar_https;
  }

  $CFGServer['CFGSiteURL'] = __removeLastSlash($url_app . $uri_base);

  /* URL da API */
  $CFGServer['CFGSiteAPI'] = __removeLastSlash($url_app . '/api') . '/';

  /* URL do /sistema */
  $CFGServer['CFGSiteURLAdm'] = $CFGServer['CFGSiteURL'] . "/sistema";

  if ($forcar_https) {
    $CFGServer['CFGSiteURL']            = str_replace("http://", "https://", $CFGServer['CFGSiteURL']);
    $CFGServer['CFGSiteURLAdm']         = str_replace("http://", "https://", $CFGServer['CFGSiteURLAdm']);
    $CFGServer['CFGSiteAPI']            = str_replace("http://", "https://", $CFGServer['CFGSiteAPI']);
    $CFGServer['CFGMostrarInfoConexao'] = _getValue($CFGServer, 'CFGMostrarInfoConexao', false);
  } else {
    /* para servir de alerta, se o sistema não está usando https de forma forçada, ele mostra a tarja */
    $CFGServer['CFGMostrarInfoConexao'] = true;
  }

  /* publicamos o novo contexto */
  $GLOBALS['CFGSiteURL']            = $CFGServer['CFGSiteURL'];
  $GLOBALS['CFGSiteURLAdm']         = $CFGServer['CFGSiteURLAdm'];
  $GLOBALS['CFGMostrarInfoConexao'] = $CFGServer['CFGMostrarInfoConexao'];
}

function _configDentroDoEscopo($InteiroOuData = 0, $forcar_https = true) {
  /**
   * Se for um inteiro só aceitamos 0 ou 1
   * Se for datahora, então precisa vir no formato yyyymmddHHMM
   **/
  $ret = false;
  if ($InteiroOuData == '0' || $InteiroOuData == '1') {
    if (!$forcar_https) {
      $ret = ($InteiroOuData == 1);
    }

  } else {
    if (strlen($InteiroOuData) == 12) {
      $hoje   = date('YmdHi');
      $limite = date('YmdHi', strtotime($hoje . ' + 7 days'));
      $ret    = ($InteiroOuData >= $hoje) && ($InteiroOuData <= $limite);
    }
  }
  return ($ret ? 1 : 0);
}

global $CFGLogFilename, $CFGLogLevel;
$CFGLogFilename = __DIR__ . "/logs/yloader.log";
$CFGLogLevel    = 1;

// ((@include_once(__DIR__."/ybasis.php")) || die("Error loading ybasis.php"));

_log("Starting...");
/* wired parts */
$yLibs = ['ybasis.php', 'ymisc.php', 'yparser.php', 'yanaliser.php', 'ydb_skeleton.php'];

$libFolder        = __DIR__;
$alternativeParts = "$libFolder/.config/yloader.lst";
/* alternative parts */
if (file_exists($alternativeParts)) {
  _log("Loading alternative parts $alternativeParts");
  foreach (file($alternativeParts) as $altPart) {
    array_push($yLibs, preg_replace('/[[:^print:]]/', '', $altPart));
  }

} else {
  _log("Not alternative parts at $libFolder/.config/yloader.lst");
}

array_push($yLibs, 'ydatabase.php');
array_push($yLibs, 'yplugins.php');

_log("Libraries to be loaded: " . json_encode($yLibs));
foreach ($yLibs as $libName) {
  $libName = trim($libName);
  if ($libName > '') {
    $_libName = "$libFolder/$libName";
    _log("Loading $_libName");
    if (file_exists($_libName)) {
      ((@include_once "$_libName") || _die("Error loading $_libName"));
    } else {
      _die("$_libName not found");
    }
  }
}
_log("Libraries ready");

$dbConfig = dirname(__FILE__) . "/api-config.ini";

if (file_exists("$dbConfig")) {

  function _configApplication() {
    global $dbConfig, $HOST, $CFGServer;

    /**
     * Há duas camadas de configuração:
     * Esta primeira (_configApplication) trata do que tem a ver com o
     * servidor como um todo. Ou seja, não é específica para uma URL determinada
     * A segunda camada (_configurarAplicativo), é puxada a partir da URL sob
     * a qual o sistema está trabalhando e nos permite sobreescrever a
     * configuração da primeira camada.
     */

    $config = parse_ini_file("$dbConfig", true);
    if (!empty($config['connection'])) {
      /* rw and/or ro are expected */
      if (!(empty($config['connection']['rw']))) {
        $rwName       = $config['connection']['rw'];
        $rwConnection = _getValue($config, $rwName, []);
        extract($rwConnection);
        $connSpec = "$dbtype#$dbhost:$dbuser@$dbname/$dbpass";
        if ($connSpec != '#:@/') {
          DBConnector::connect($dbtag, $connSpec, "rw");
        }

      }

      if (!empty($config['connection']['ro'])) {
        $roNamesList = explode(",", $config['connection']['ro']);
        foreach ($roNamesList as $roName) {
          $roConnection = _getValue($config, $roName, []);
          extract($roConnection);
          $connSpec = "$dbtype#$dbhost:$dbuser@$dbname/$dbpass";
          if ($connSpec != '#:@/') {
            DBConnector::connect($dbTag, $connSpec, "ro");
          }
        }
      }

    }

    // site
    if (isset($config['site'])) {
      extract($config['site']);
    }

    /* Local onde estão os arquivos */
    $CFGServer['CFGSiteFolder'] = __removeLastSlash(isset($site_folder) ? $site_folder : "/public_html");

    $token = __genRandomToken(true);

    $CFGServer['CFGToken'] = $token;

    /* URL do site */
    $uri_base     = isset($uri_base) ? $uri_base : "/";
    $forcar_https = empty($forcar_https) ? false : ($forcar_https == 1);
    if (!empty($_SERVER['REQUEST_SCHEME'])) {
      __definirURL($_SERVER['REQUEST_SCHEME'] . "://" . $_SERVER['HTTP_HOST'], $uri_base, $forcar_https);
    } else {
      __definirURL((!empty($site_url) ? $site_url : "http://127.0.0.1"), $uri_base, $forcar_https);
    }

    //debug
    if (isset($config['debug'])) {
      extract($config['debug']);
    }

    $CFGServer['CFGLogFilename']             = replaceFilenameExtension((isset($log) ? $log : (__DIR__ . "/logs/application.log")), "$HOST.log");
    $CFGServer['CFGLogLevel']                = (isset($logLevel) ? $logLevel : 0);
    $CFGServer['CFGOcultarErros']            = (isset($ocultarErros) ? $ocultarErros : 0);
    $CFGServer['CFGDepurarCalculoEstagio']   = isset($depurarCalculoEstagio) ? ($depurarCalculoEstagio == 1) : false;
    $CFGServer['CFGPermitirLoginInativo']    = isset($permitirLoginInativo) ? (_configDentroDoEscopo($permitirLoginInativo, $forcar_https)) : false;
    $CFGServer['CFGPermitirLoginIncompleto'] = isset($permitirLoginIncompleto) ? (_configDentroDoEscopo($permitirLoginIncompleto, $forcar_https)) : false;
    $CFGServer['CFGMostrarInfoConexao']      = isset($mostrarInfoConexao) ? $mostrarInfoConexao : 0;
    $CFGServer['CFGUsarEmailInstitucional']  = intval((isset($usarEmailInstitucional) ? intval($usarEmailInstitucional) : 0));
    $CFGServer['CFGDesviarEmail']            = intval(isset($desviarEmail) ? $desviarEmail : 0);
    $CFGServer['CFGEmailDesvio']             = isset($emailDesvio) ? $emailDesvio : "nowhere@example.com";

    /**
     * Por padrão, se a pasta de logs não existe ou não pode ser escrita,
     * o sistema não oculta os erros
     **/
    if (!is_writable(dirname($CFGServer['CFGLogFilename']))) {
      $CFGOcultarErros = 0;
    }

    if (isset($ocultarErros) && ($ocultarErros == 1)) {
      ini_set('display_errors', 1);
      ini_set('display_startup_errors', 1);
      error_reporting(E_ALL);
      $CFGServer['CFGMostrarErrorPHP'] = 1;
    } else {
      ini_set('display_errors', 0);
      ini_set('display_startup_errors', 0);
      error_reporting(0);
      $CFGServer['CFGMostrarErrorPHP'] = 0;
    }

    // curl
    if (isset($config['curl'])) {
      extract($config['curl']);
    }

    $CFGServer['CFGCurlProxy'] = isset($proxy) ? $proxy : '';

    // sms e gw de pagamento

    /**
     * SMS
     **/
    $CFGServer['CFGSMSGatewayList'] = [];
    foreach ($config as $key => $configElement) {
      if (substr($key, 0, 4) == 'sms_') {
        /**
         * A chave de configuração é sms_gateway
         * sms_ é constante
         * gateway pode ser qualquer um dos conhecidos: zenvia, locasms, easypark, iagente
         **/
        preg_match_all('/(([a-zA-Z]{1}[a-zA-Z]*))/', $key, $auxGateway);
        if ($auxGateway) {
          if (count($auxGateway[0]) == 2) {
            /* apenas adicionamos as configuracoes habilitadas */
            if (!empty($configElement['enabled'])) {
              if ($configElement['enabled'] == 1) {
                $auxNdx                                             = count($CFGServer['CFGSMSGatewayList']);
                $CFGServer['CFGSMSGatewayList'][$auxNdx]            = $configElement;
                $CFGServer['CFGSMSGatewayList'][$auxNdx]['gateway'] = $auxGateway[0][1];
              }

            }
          }
        }
      }
    }

    /* sorteamos uma config */
    $auxNdxRnd = mt_rand(0, max(0, count($CFGServer['CFGSMSGatewayList']) - 1));
    $auxNdx    = max(0, $auxNdxRnd);
    if (!empty($CFGServer['CFGSMSGatewayList'][$auxNdxRnd])) {
      $CFGServer['CFGSMSGatewayList']['default'] = $auxNdxRnd;
    }
    $CFGServer['CFGSMSConfigNdx']  = $auxNdx;
    $CFGServer['CFGSMSConfigName'] = _getValue($CFGServer['CFGSMSGatewayList'], $auxNdx, ['gateway' => 'none'])['gateway'];

    /**
     * Envio padrão de e-mail
     * É sobreescrito pela configuração
     **/
    $CFGServer['CFGEmailSMTPServers'] = [];

    /**
     * Envio de e-mail
     * O envio de e-mail permite indicar um conjunto de servidores de e-mail
     * que serão usados de forma aleatoria
     * Cada nome de servidor de SMTP é formado pelo nome dado em integration.smtp
     * seguido de _cfg_ e um nro de sequencia de 0-9
     **/

    $auxSMTPBase = _getValue($CFGServer, 'CFGEmailSMTP', 'none') . "_cfg_";

    $auxNdx = 0;
    for ($i = 0; $i < 9; $i++) {
      if (isset($config[$auxSMTPBase . $i])) {
        $enabled = 0;
        if (!empty($host)) {
          unset($host);
        }

        if (!empty($username)) {
          unset($username);
        }

        if (!empty($port)) {
          unset($port);
        }

        if (!empty($password)) {
          unset($password);
        }

        if (!empty($enabled)) {
          unset($enabled);
        }

        if (!empty($api_user)) {
          unset($api_user);
        }

        if (!empty($api_key)) {
          unset($api_key);
        }

        if (!empty($server_type)) {
          unset($server_type);
        }

        /* espera host, username, port e password em base64 */
        extract($config[$auxSMTPBase . $i]);

        if (((isset($host)) && (isset($username)) && (isset($port)) && (isset($password))) ||
          ((isset($api_user)) && (isset($api_key)))) {

          $enabled  = intval($enabled > 0);
          $password = (!empty($password) ? base64_decode($password) : null);
          if (!isset($CFGServer['CFGEmailSMTPServers'][$CFGServer['CFGEmailSMTP']])) {
            $CFGServer['CFGEmailSMTPServers'][$CFGServer['CFGEmailSMTP']] = array();
          }
          $CFGServer['CFGEmailSMTPServers'][$CFGServer['CFGEmailSMTP']][$auxNdx++] = array(
            'host'        => (isset($host) ? $host : null),
            'user'        => (isset($username) ? $username : null),
            'port'        => (isset($port) ? $port : null),
            'password'    => (isset($password) ? $password : null),
            'enabled'     => $enabled,
            'api_user'    => (isset($api_user) ? $api_user : ''),
            'api_key'     => (isset($api_key) ? $api_key : ''),
            'server_type' => (!empty($server_type) ? $server_type : 'smtp'),
          );
        }
      }
    }

    /* ele sorteia um servidor de e-mail dentre os habilitados*/
    do {
      $auxNdxRnd = mt_rand(0, max(0, $auxNdx - 1));
    } while (($auxNdx > 0) &&
      (!empty($CFGServer['CFGEmailSMTPServers'][$auxNdxRnd])) &&
      (!$CFGServer['CFGEmailSMTPServers'][$auxNdxRnd]['enabled']));

    $CFGServer['CFGEmailSMTPConfigNdx'] = $auxNdxRnd;
    /* a partir daqui, o sistema (neste ciclo) usará o servidor configurado em CFGEmailSMTPConfig */
    $CFGServer['CFGEmailSMTPConfig'] = _getValue($CFGServer['CFGEmailSMTPServers'], _getValue($CFGServer, 'CFGEmailSMTP', 'none'), [0 => []])[$auxNdxRnd];

    /* publicar CFGServer */
    foreach ($CFGServer as $key => $value) {
      $GLOBALS[$key] = $value;
    }

    $CFGServer['configured'] = true;

    if (false) {echo "<pre>";die(print_r($CFGServer));}

  }
  _configApplication();
} else {
  _response("'$dbConfig' não localizado");
}

/**
 * Agencias
 **/
function _configurarAplicativo($url_app = '') {
  global $CFGServer, $CFGApp, $HOST;

  if ($url_app > '') {
    $CFGApp['configured'] = false;
  }

  $ret = false;

  if (!$CFGApp['configured']) {
    $appData = DBConnector::grantCollection(null, 'app_config', 'id');

    if ($url_app == '') {
      $url_app = _getValue($appData->get("default_domain=true"), 'url', DEFAULT_HOST);
    } else {
      __definirURL($url_app);
    }
    _log("Configurando aplicativo '$url_app'");

    $url_app_e_ip = filter_var($url_app, FILTER_VALIDATE_IP);
    $_404_causa   = "Endereço desconhecido";
    $ret          = false;

    if ($url_app_e_ip) {
      $_404_causa = "Use a URL e não o endereço IP";
      $ret        = false;
    } else {
      $layout_app = $appData->get("url like '%$url_app'");

      if ($layout_app) {
        $CFGApp['id']           = _getValue($layout_app, 'id', '');
        $CFGApp['url_app']      = _getValue($layout_app, 'url', '');
        $CFGApp['chat_agencia'] = _getValue($layout_app, 'chat_agencia', '');
        $CFGApp['chat_jivo_id'] = _getValue($layout_app, 'chat_jivo_id', '');

        $CFGApp['configured'] = true;

        foreach ($CFGApp as $key => $value) {
          $_SESSION[$key] = $value;
        }
        $ret = true;
      } else {
        $_404_causa = "URL desconhedida no servidor.";
        $ret        = false;
      }
    }
  } else {
    $ret = true;
  }
  if (!isset($_404_causa)) {
    $_404_causa = "Erro desconhecido";
  }

  return [$ret, (!$ret ? $_404_causa : '')];
}

function _getTemplate($templateName) {
  $folder   = dirname(__FILE__);
  $fileName = $folder . '/templates/' . $templateName;
  $ret      = false;
  if (file_exists($fileName)) {
    $ret = file_get_contents($fileName);
  }
  return $ret;
}

function minifyHtml($buffer) {

  $search = array(
    '/\>[^\S ]+/s', // strip whitespaces after tags, except space
    '/[^\S ]+\</s', // strip whitespaces before tags, except space
    '/(\s)+/s', // shorten multiple whitespace sequences
    '/<!--(.|\s)*?-->/', // Remove HTML comments
  );

  $replace = array(
    '>',
    '<',
    '\\1',
    '',
  );

  $buffer = preg_replace($search, $replace, $buffer);

  return $buffer;
}

/**
 * Validação de valores
 * #     #
 * #     #    ##    #          #    #####     ##     ####    ####   ######   ####
 * #     #   #  #   #          #    #    #   #  #   #    #  #    #  #       #
 * #     #  #    #  #          #    #    #  #    #  #       #    #  #####    ####
 *  #   #   ######  #          #    #    #  ######  #       #    #  #            #
 *   # #    #    #  #          #    #    #  #    #  #    #  #    #  #       #    #
 *    #     #    #  ######     #    #####   #    #   ####    ####   ######   ####
 **/
function getDomain($url) {
  $pieces = parse_url($url);
  $domain = isset($pieces['host']) ? $pieces['host'] : '';
  if (preg_match('/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,6})$/i', $domain, $regs)) {
    return $regs['domain'];
  }
  return false;
}
