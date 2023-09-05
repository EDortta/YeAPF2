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
    $configFolder = self::getApplicationFolder()."/config";

    if (is_dir($configFolder)) {
      $configFolder = realpath($configFolder);
    }

    return $configFolder;
  }

  public static function canWorkWithoutAssets(): bool {
    return false;
  }

  public static function getGLobalAssetsFolder(): string {
    $assetsFolder = self::getApplicationFolder()."/assets";
    $folderOk = is_dir($assetsFolder);
    if (!$folderOk) {
      $folderOk = mkdir($assetsFolder, 0777, true);
      if (!$folderOk) {
        throw new \Exception("Assets folder $assetsFolder cannot be created", 1);
      }
    }
    $folderOk = $folderOk && is_writable($assetsFolder);
    if (!$folderOk) {
      if (!self::canWorkWithoutAssets()) {
        throw new \Exception("Assets folder $assetsFolder cannot be written", 1);
      }
    }

    if ($folderOk)
      $assetsFolder = realpath($assetsFolder);

    return $assetsFolder;

  }

  public static function open() {
    if (empty(self::$configAreas)) {
      $configFolder = self::getAssetsFolder();
      echo __FILE__.":".__LINE__;
      echo "CONFIG DEVICE: " . $configFolder . "\n";
      \_log("Reading configuration files from $configFolder");
      foreach (scandir($configFolder) as $file) {
        // echo "[ $file ]";
        if (strpos($file, ".json") !== false) {
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
