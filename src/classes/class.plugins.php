<?php
declare (strict_types = 1);
namespace YeAPF\Plugins;

/**
 * PluginList keep a list os the plugins
 *
 * All the plugins get registered on this object.
 * Unregistered plugins can't run.
 * This is the object that is queried by the services and by the tools.
 */
class PluginList {
  /**
   * List of registered plugins
   *
   * @var array
   */
  static private $plugins = [];

  /**
   * Register a plugin in the list.
   *
   * In order to be used by deployment and tools, it receives the filename
   * and not only the object itself. That means that the proxy generator
   * can just load all the plugin tree and them query it objects.
   * In case the plugin is already registered, it triggers an exception.
   *
   * @param object $object Children of ServicePlugin
   * @param string $filename File location
   *
   * @return bool true if succeful
   */
  static public function registerPlugin(object $object, string $filename) {
    $ret       = false;
    $className = get_class($object);
    if (empty(self::$plugins[$className])) {
      self::$plugins[$className] = [
        'filename' => $filename,
        'object'   => $object,
      ];
      $ret = true;
    } else {
      //   throw new \Exception("$className already registered");
    }
    return $ret;
  }

  /**
   * Return a list of registered plugins.
   *
   * @return mixed
   */
  static public function getPluginNames() {
    $ret = [];
    foreach (self::$plugins as $objectName => $objectDefinition) {
      $ret[$objectName] = $objectDefinition['filename'];
    }
    return $ret;
  }

  /**
   * Return the plugin by it name
   *
   * It returns the object, so the filename is not present here.
   * If the plugin does not exists, it returns the DummyPlugin.
   *
   * @param string $pluginName
   *
   * @return object
   */
  static public function getPluginByName(string $pluginName) {
    return self::$plugins[$pluginName]['object'] ?? $GLOBALS['dummyPlugin'];
  }

  /**
   * Load all the plugins present in a folder
   *
   * It is a recursive capable function, so if a folder is inside the
   * indicated folder, it will follow that.
   *
   * @param string $folder
   *
   * @return void
   */
  static public function loadPlugins(string $folder, int $level = 10) {
    if ($level > 0) {
      if (is_dir($folder)) {
        \_log("Plugin loader from '$folder'");
        if ($dh = opendir($folder)) {
          while (($filename = readdir($dh)) !== false) {
            if (!is_dir("$folder/$filename")) {
              $ext = pathinfo($filename, PATHINFO_EXTENSION);
              if ("php" === $ext) {
                \_log("  Loading '$filename' plugin\n");
                require_once "$folder/$filename";
                \YeAPF\checkClassesRequirements();
              }
            } else {
              if (!in_array($filename, ['.', '..'])) {
                self::loadPlugins($filename, $level - 1);
              }
            }
          }
          closedir($dh);
        }
        \_log("Plugins ready");
      }
    }
  }
}
