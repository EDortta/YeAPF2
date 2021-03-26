<?php
/**
 * Project: %APP_NAME%
 * Version: %core_VERSION_SEQUENCE%
 * Date: %core_VERSION_DATE%
 * File: %FILE_NAME%
 * Last Modification: %LAST_FILE_MODIFICATION%
 * %COPYRIGHT_NOTE%
 **/

interface YeapfPlugin {
  /**
   * Chamado assim que o plugin é carregado, tem por função
   * inicializar o mesmo e prepara-lo para funcionar.
   * É um método puro devolvendo apenas true ou false se
   * a inicialização deu certo.
   * (domain) é o dominio sob o qual o plugin está funcionando
   * (gateway) é a via pela que o sistema está chamando o plugin:
   * api, web ou cli
   * (context) é um vetor associativo com o contexto operacional
   * em que o plugin está operando.
   */
  public function initialize($domain, $gateway, $context);

  /**
   * É o miolo do plugin.
   * Por meio deste método a API e a WEB se comunicam com o plugin.
   * O primeiro parâmetro (subject) é em geral um substantivo
   * ao passo que o segundo (action) indica a ação a ser realizada sob
   * o substantivo anterior.
   * Já os outros parâmetros podem ser qualquer coisa que o programador
   * precisar.
   *
   */
  public function do($subject, $action, ...$params);
}

class PluginManager {
  static $plugins        = null;
  static $currentGateway = null;

  public function __construct() {
    $GLOBALS['__yPluginsRepo'] = [];
    if (__outputIsJson()) {
      self::$currentGateway = 'api';
    } else {
      if (php_sapi_name() == "cli") {
        self::$currentGateway = 'cli';
      } else {
        self::$currentGateway = 'web';
      }
    }
  }

  static public function loadPlugins($folder) {
    global $yAnaliser, $CFGContext;
    $currentDomain = mb_strtolower(getDomain(_getValue($CFGContext, 'CFGSiteURL', '')));
    if (is_dir("$folder")) {
      $pluginsFolder = scandir("$folder");
      foreach ($pluginsFolder as $plugin) {
        if (!($plugin == '.' || $plugin == '..')) {
          if (is_dir("$folder/$plugin")) {
            $pluginIniFile = "$folder/$plugin/config.ini";
            if (file_exists($pluginIniFile)) {
              $pluginIni    = @parse_ini_file($pluginIniFile, true);
              $pluginConfig = _getValue($pluginIni, 'config', []);
              foreach ($pluginConfig as $sequence => $pluginName) {
                preg_match('/plugin[_]{0,1}[0-9]*/', $sequence, $sequenceId);
                if (!empty($sequenceId[0])) {
                  /*
                   * Plugin cannot be repeated
                   * Missed the exceptions: login, logoff, etc...
                   */
                  if (!array_key_exists($pluginName, $GLOBALS['__yPluginsRepo'])) {
                    $GLOBALS['__yPluginsRepo'][$pluginName]           = $pluginIni[$pluginName];
                    $GLOBALS['__yPluginsRepo'][$pluginName]['loaded'] = false;
                    $GLOBALS['__yPluginsRepo'][$pluginName]['folder'] = "$folder/$plugin";

                    // API helper
                    $GLOBALS['__yPluginsIndex'][$pluginIni[$pluginName]['class']] = $pluginName;
                  }
                }
              }
            } else {
              _warn("Plugin configuration file '$pluginIniFile' not found");
            }

            /*
             * Plugin must be enabled in the current domain
             * or on any domain
             */
            foreach ($GLOBALS['__yPluginsRepo'] as $pluginName => $pluginConfig) {
              if (!$pluginConfig['loaded']) {
                if ($pluginConfig['enabled']) {
                  $domains = mb_strtolower(',' . str_replace(';', ',', str_replace(' ', '', $pluginConfig['domains'])) . ',');
                  if ($pluginConfig['domains'] == '*' || strpos($domains, ",$currentDomain,") !== false) {
                    $GLOBALS['__yPluginsRepo'][$pluginName]['enabled'] = true;
                  }

                  _log("Plugin $pluginName can run in $currentDomain? " . ($pluginConfig['enabled'] ? 'YES' : 'NO'));

                  /**
                   * If the plugin is enabled and can be used in this domain,
                   * search the class and create an instance
                   */
                  if ($GLOBALS['__yPluginsRepo'][$pluginName]['enabled']) {
                    $_pluginScriptname = getcwd() . "/" . __removeLastSlash($pluginConfig['folder']) . '/' . _getValue($pluginConfig, 'script', 'plugin.php');
                    if (file_exists($_pluginScriptname)) {
                      $GLOBALS['__yPluginsRepo'][$pluginName]['exists'] = true;

                      _log("Plugin loading $_pluginScriptname");

                      /**
                       * Each parameter is analised in order to help in build the plugin context
                       */
                      foreach ($pluginConfig as $key => $value) {
                        $aux                                          = $yAnaliser->do($value, $CFGContext);
                        $GLOBALS['__yPluginsRepo'][$pluginName][$key] = $aux;
                      }

                      ((@include_once "$_pluginScriptname") || _die("Error loading $_pluginScriptname"));
                      $GLOBALS['__yPluginsRepo'][$pluginName]['loaded'] = true;

                      $_pluginClassName = _getValue($pluginConfig, 'class', 'DefaultPlugin');
                      _log("Plugin class: $_pluginClassName");

                      if (class_exists($_pluginClassName)) {
                        /**
                         * if class exists, it is instantiated
                         */
                        _log("Plugin being instantiated as $_pluginClassName");
                        $GLOBALS['__yPluginsRepo'][$pluginName]['_class'] = new $_pluginClassName();
                        if ($GLOBALS['__yPluginsRepo'][$pluginName]['_class']) {
                          /**
                           * once the class is instantiated, it's initialized
                           * If the initializator returns true, the plugin is enabled.
                           */
                          $gateway                                           = self::$currentGateway;
                          $GLOBALS['__yPluginsRepo'][$pluginName]['enabled'] = $GLOBALS['__yPluginsRepo'][$pluginName]['_class']->initialize($currentDomain, $gateway, $CFGContext);
                        }
                      }
                    } else {
                      _log("Plugin $_pluginScriptname not found");
                      $GLOBALS['__yPluginsRepo'][$pluginName]['exists'] = false;
                    }
                  }
                }
              }
            }
          }
        }
      }
    }
  }

  static public function callPlugin($subject, $action, ...$params) {
    global $yAnaliser, $CFGContext;
    $gateway = self::$currentGateway;
    $ret     = '';
    foreach ($GLOBALS['__yPluginsRepo'] as $key => $pluginDefinition) {
      if ($key == $subject) {
        if (!empty($pluginDefinition['_class'])) {
          $pluginDefinition['_class']->do($subject, $action, $params);
        } else {
          if (!empty($pluginDefinition['file'])) {
            $wd = getcwd();
            if (chdir($pluginDefinition['folder'])) {
              $content = file_get_contents($pluginDefinition['file']);
              /**
               * replace script references
               */
              $content = preg_replace('/src[\ ]*=[\ ]*([\'"]){0,1}([a-zA-Z_0-9\.]{1}[a-zA-Z0-9\.\/_]*)([\'"]){0,1}/', 'src="#cwd()/$2"', $content);
              $content = preg_replace('/link(.*)href[\ ]*=[\ ]*([\'"]){0,1}([a-zA-Z_0-9\.]{1}[a-zA-Z0-9\.\/_]*)([\'"]){0,1}/', 'link$1href="#cwd()/$3"', $content);

              /**
               * apply the preprocessor
               */
              $ret = $yAnaliser->do($content, $CFGContext);
              chdir($wd);
            }
          }
        }
      }
    }
    return $ret;
  }
}

$pluginManager = new PluginManager();
