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
 * YeAPF plugin interface
 *
 * All Plugins must implement at least the methods here declared.
 * Outside that, the Plugin can behave as any other class.
 */
interface YeapfPlugin
{

  /**
   * Plugin Initializator.
   *
   * Called as soon as the plugin is loaded, it has the function
   * of initialize it and get it ready to work.
   * It is a pure method returning only true or false if the
   * initialization worked.
   * @param string $domain  the domain under which the plugin is working
   * @param string $gateway the means by which the system is calling
   *                        the plugin: api, web or cli
   * @param string $context an associative vector with the operational
   *                        context the plugin is operating on.
   * @return boolean  Indicates if the plugin can or cannot be used.
   */

  public function initialize($domain, $gateway, &$context);

  /**
   * plugin core method.
   *
   * It is the core of the plugin.
   * Through this method, the API and the WEB communicate
   * with the plugin.
   * When it is called from api, in a positional URL, the
   * first parameter will be the subject and the second
   * the action. As in /clients/list.
   *
   * @param string $subject It's ussualiy a noun telling the system
   *                        the subject, or chapter on where the
   *                        action will take place
   * @param string $action  It uses to be a verb that indicates the
   *                        action to be performed under the previous
   *                        noun.
   *
   * @return yReturnType
   */
  public function do($subject, $action, ...$params);
}

class PluginManager
{
  static $plugins        = null;
  static $currentGateway = null;

  public function __construct()
  {
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

  static public function loadPlugins($folder)
  {
    global $yAnaliser, $CFGContext;
    $currentDomain = mb_strtolower(getDomain(_getValue($CFGContext, 'CFGSiteURL', '')));
    if (is_dir("$folder")) {
      $pluginsFolder = scandir("$folder");
      foreach ($pluginsFolder as $plugin) {
        if (!($plugin == '.' || $plugin == '..')) {
          $isBasisConfigFile = ('basis' == $folder && $plugin == 'config.ini');
          $isFolderIntoBasis = ('basis' == $folder && is_dir("$folder/$plugin"));

          echo "[ $folder/$plugin ]\n";
          if (!$isFolderIntoBasis) {
            if (is_dir("$folder/$plugin") || $isBasisConfigFile) {

              if ($isBasisConfigFile) {
                /**
                 * basis is an special folder
                 * it has the right to have a config.ini in the main folder in order
                 * to configure all the system
                 */
                $pluginIniFile = "$folder/config.ini";
              } else {
                $pluginIniFile = "$folder/$plugin/config.ini";
              }

              echo "$pluginIniFile\n";

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
                      $GLOBALS['__yPluginsRepo'][$pluginName]['folder'] = ($isBasisConfigFile ? "$folder" : "$folder/$plugin");

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
  }

  static public function callPlugin($subject, $action, ...$params)
  {
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
