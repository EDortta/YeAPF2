<?php
declare (strict_types = 1);
namespace YeAPF;

class YeAPFConfig  {
  use \YeAPF\Assets;
  static private $configAreas = [];
  static private $config      = null;

  public static function getConfigFile() {
    return self::$configFile;
  }

  public static function getAssetsFolder(): string {
    return realpath(__DIR__ . "/../config");
  }
  public static function canWorkWithoutAssets(): bool {
    return false;
  }

  public static function getGLobalAssetsFolder(): string {
    if (!is_dir(__DIR__."/../assets")) {
      mkdir(__DIR__."/../assets", 0777, true);
    }
    return realpath(__DIR__ . "/../assets");
  }

  public static function open() {
    if (empty(self::$configAreas)) {
      $configFolder = self::getAssetsFolder();
      echo "CONFIG DEVICE: " . $configFolder . "\n";
      \_log("Reading configuration files from $configFolder");
      foreach (scandir($configFolder) as $file) {
        if (strpos($file, ".json") !== false) {
          // _log("$file ... ".basename($file, ".json"));
          $config = file_get_contents($configFolder . "/" . $file);
          if ($config !== false) {
            self::$configAreas[basename($file, ".json")] = json_decode($config, false);
            if (null == self::$configAreas[basename($file)] && json_last_error() !== JSON_ERROR_NONE) {
              throw new \Exception("Config file " . $configFolder . "/" . $file . " cannot be parsed", 1);
            }
          } else {
            throw new \Exception("Config file " . $configFolder . "/" . $file . " cannot be readed", 1);
          }
        }
      }

    }

  }

  public static function getSection($area, $section = null) {
    self::open();
    $ret = null;
    if (!empty(self::$configAreas[$area])) {
      if (null == $section) {
        $ret = self::$configAreas[$area];
      } else {
        $ret = self::$configAreas[$area]->$section ?? null;
      }

    }
    return $ret;
  }
}
