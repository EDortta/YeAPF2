<?php declare(strict_types=1);

namespace YeAPF;

class yLogger
{
  use \YeAPF\Assets;

  static private $logFolder = null;
  static private $lastFile = null;
  static private $syslogOpened = false;
  static private $areas =[];
  static private $minLogLevel = YeAPF_LOG_WARNING;

  /**
   * Retrieves the path to the assets folder.
   *
   * @return string The path to the assets folder.
   */
  static function getAssetsFolder(): string
  {
    $logFolder = self::getApplicationFolder() . '/logs';

    if (is_dir($logFolder)) {
      $logFolder = realpath($logFolder);
    }

    return $logFolder;
  }

  static function canWorkWithoutAssets(): bool
  {
    return true;
  }

  static private function startup()
  {
    if (null == self::$logFolder) {
      self::$logFolder = self::getAssetsFolder();
      // echo "LOG DEVICE: " . self::$logFolder . "\n";
      if (!is_dir(self::$logFolder)) {        
        mkdir(self::$logFolder, 0777, true) || throw new \Exception("Log folder ".self::$logFolder." cannot be created", 1);
      }
    }
    $ret = is_dir(self::$logFolder) && is_writable(self::$logFolder);
    return $ret;
  }

  static public function defineLogTagAndLevel(string $tag, int $option)
  {
    if (self::$syslogOpened) {
      closelog();
      self::$syslogOpened = false;
    }
    self::$syslogOpened = openlog($tag, $option, LOG_LOCAL0);
  }

  static public function defineLogFilters(array $areas, int $logLevel) {
    self::$areas = $areas;
    self::$minLogLevel = $logLevel;
  }

  static public function log(int $area, int $warningLevel, string $message)
  {
    global $currentURI;

    if (self::startup()) {
      $dbg = debug_backtrace();
      $time = date('h:i:s ');
      $preamble = "$time";
      if (self::$lastFile != $dbg[1]['file']) {
        self::$lastFile = $dbg[1]['file'];
        $preamble .= self::$lastFile . "---\n$time";
        // echo json_encode($dbg[1],JSON_PRETTY_PRINT);
      }
      if ($currentURI > '') {
        $preamble .= '  ' . str_pad(' ' . $dbg[1]['line'], 4, ' ', STR_PAD_LEFT) . ': [' . $currentURI . '] ';
      } else {
        $preamble .= '  ' . str_pad(' ' . $dbg[1]['line'], 4, ' ', STR_PAD_LEFT) . ': ';
      }

      $message = str_replace("\n", ' ', $message);
      if (trim($message) > '') {
        syslog(LOG_INFO, $message);

        $fileName = self::$logFolder . '/' . date('Y-m-d') . '.log';
        $fp = fopen($fileName, 'a+');
        if ($fp) {
          fwrite($fp, "$preamble $message\n");
          fclose($fp);
        }
      }
    }
  }
}
