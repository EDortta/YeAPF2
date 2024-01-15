<?php
declare(strict_types=1);
namespace YeAPF;

trait Assets {
    static function getApplicationFolder(): string {
      if (!isset($_SERVER['SCRIPT_FILENAME'])) {
        $mainScriptFolder = __DIR__."/../..";
      } else {
        $mainScriptPath = $_SERVER['SCRIPT_FILENAME'];
        $mainScriptFolder = dirname($mainScriptPath);
      }

      return $mainScriptFolder;
    }

    abstract static function getAssetsFolder(): string;

    static function grantAssetsFolder(): bool {
        $folder = self::getAssetsFolder();
        $ret=is_dir($folder);
        if (!$ret) {
          $ret=mkdir($folder, 0777, true);
        }
        if (!$ret) {
          if (!self::canWorkWithoutAssets()) {
            throw new \Exception("Assets folder $folder cannot be created", 1);
          }
        }

        $ret = $ret && is_writable($folder);
        if (!$ret) {
          if (!self::canWorkWithoutAssets()) {
            throw new \Exception("Assets folder $folder cannot be written", 1);
          }
        }
        return $ret;
    }

    abstract static function canWorkWithoutAssets(): bool;

    static function assetsFolderExists(): bool {
        $folder=self::getAssetsFolder();
        $ret = is_dir($folder);
        if (!$ret) {
          if (function_exists("\_log")) {
            \_log("Assets folder $folder does not exist");
          }
        }
        return $ret;
    }

    static function assetsFolderIsWritable(): bool {
        return self::assetsFolderExists() && is_writable(self::getAssetsFolder());
    }
}


function checkClassesRequirements() {
    static $definedClasses;
    if (null==$definedClasses) {
        $definedClasses = get_declared_classes();
    } else {
        $updatedClasses = get_declared_classes();
        $newClasses = array_diff($updatedClasses, $definedClasses);
        foreach ($newClasses as $newClass) {
            if (substr($newClass, 0, 5) == "YeAPF") {
                if (trait_exists('\YeAPF\Assets', false) && class_uses($newClass, true)) {
                // echo "$newClass uses \YeAPF\Assets\n";
                if (!$newClass::canWorkWithoutAssets()) {
                    // echo "  $newClass cannot work without assets folder\n";
                    if (!$newClass::assetsFolderExists()) {
                    // echo "  $newClass assets folder does not exist or is not writable\n";
                    $newClass::grantAssetsFolder();
                    } else {
                    // echo "  $newClass assets folder (". $newClass::getAssetsFolder() .") exists and is writable\n";
                    }
                }
                } else {
                // echo "$newClass does not use \YeAPF\Assets\n";
                }
            }
        }
        $definedClasses = array_merge($definedClasses, $updatedClasses);
    }
}


checkClassesRequirements();