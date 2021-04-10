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
 * Plugin interface
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

/**
 * PluginManager Class
 *
 * All kind of plugins will be managed by this class.
 * Basis is a single plugin that is loaded prior to others.
 * Modules are considered as first class plugins.
 * Plugins are treated as customer addition to main system.
 */
class PluginManager
{
  /**
   * List of loaded plugins
   *
   * @var        array
   */
  static $plugins        = null;
  /**
   * Current gateway under wich this application is running... now
   *
   * @var        string
   */
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

  /**
   * Load the plugins present in the specified folder
   *
   * This method is used by api.php, index.php and others where is
   * needed to load the plugins from a folder.
   * Meanwhile the order inside the folder is controlled by this
   * method, the order in which the folders are loaded are controled
   * by the caller.
   *
   * @param string $folder indicates from which folder the plugins
   *                        will be loaded.
   *
   * @return null
   */
  static public function loadPlugins($folder)
  {
    global $yAnalyzer, $CFGContext;
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
                          $aux                                          = $yAnalyzer->do($value, $CFGContext);
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

  /**
   * Plugin caller
   *
   * Is the responsible method used to call a plugin, any of them.
   * Only if the plugin attend to the subject, is called.
   * The first plugin to answer makes the algorithm break the search.
   * If the plugin has a class, then the do() method of this class
   * will be called, otherwise, if there is a file (a.k.a. an html)
   * then this file will be processed but first, the manager changes
   * the current folder to the file folder in order to enforces an
   * isolated environment for the plugin.
   *
   * @param      string  $subject    Usually a noun
   * @param      string  $action     Usually a verb
   * @param      mixed   ...$params  A set of parameters sent by URL,
   *                                 a POST array, a JSON body, etc
   *
   * @return     string  The return of the executed plugin. In case
   *                     of being a plugin class which answer the call
   *                     then, it is an encoded json block.
   */
  static public function callPlugin($subject, $action, ...$params)
  {
    global $yAnalyzer, $CFGContext;
    $gateway = self::$currentGateway;
    $answered = false;
    $ret     = '';
    foreach ($GLOBALS['__yPluginsRepo'] as $key => $pluginDefinition) {
      if (!$answered) {
        if ($key == $subject) {
          if (!empty($pluginDefinition['_class'])) {
            $ret = json_encode($pluginDefinition['_class']->do($subject, $action, $params));
            $answered = true;
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
                $ret = $yAnalyzer->do($content, $CFGContext);
                $answered = true;
                chdir($wd);
              }
            }
          }
        }
      }
    }
    return $ret;
  }
}

$pluginManager = new PluginManager();
