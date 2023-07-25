<?php
declare (strict_types = 1);
namespace YeAPF;

class yLogger {
  use \YeAPF\Assets;

  static private $logFolder = null;
  static private $lastFile  = null;

  static function getAssetsFolder(): string {
    if (!is_dir(__DIR__ . "/../../logs")) {
      mkdir(__DIR__ . "/../../logs", 0777, true);
    }
    return realpath(__DIR__ . "/../../logs");
  }

  static function canWorkWithoutAssets(): bool {
    return true;
  }

  static private function startup() {
    if (null == self::$logFolder) {
      self::$logFolder = self::getAssetsFolder();
      echo "LOG DEVICE: " . self::$logFolder . "\n";
      if (!is_dir(self::$logFolder)) {
        mkdir(self::$logFolder, 0777, true);
      }
    }
    $ret = is_dir(self::$logFolder) && is_writable(self::$logFolder);
    return $ret;
  }

  static public function log(int $area, int $warningLevel, string $message) {
    global $currentURI;
    if (self::startup()) {
      $dbg      = debug_backtrace();
      $time     = date("h:i:s ");
      $preamble = "$time";
      if (self::$lastFile != $dbg[1]["file"]) {
        self::$lastFile = $dbg[1]["file"];
        $preamble .= self::$lastFile . "---\n$time";
        // echo json_encode($dbg[1],JSON_PRETTY_PRINT);
      }
      if ($currentURI > '') {
        $preamble .= "  " . str_pad(" " . $dbg[1]['line'], 4, ' ', STR_PAD_LEFT) . ": [" . $currentURI . "] ";
      } else {
        $preamble .= "  " . str_pad(" " . $dbg[1]['line'], 4, ' ', STR_PAD_LEFT) . ": ";
      }

      // echo $preamble;
      $message = str_replace("\n", " ", $message) . "\n";
      // echo "$message";
      $fileName = self::$logFolder . "/" . date("Y-m-d") . ".log";
      $fp       = fopen($fileName, "a+");
      if ($fp) {
        fwrite($fp, "$preamble $message");
        fclose($fp);
      }
    }
  }
}
