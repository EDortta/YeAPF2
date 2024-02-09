<?php declare(strict_types=1);

namespace YeAPF;

class yLogger
{
  use \YeAPF\Assets;

  static private $logFolder = null;
  static private $lastFile = null;
  static private $syslogOpened = false;
  static private $areas = [];
  static private $minLogLevel = YeAPF_LOG_WARNING;
  static private $logFileHandler = null;

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
      // print_r("LOG DEVICE: " . self::$logFolder."\n");
      if (!is_dir(self::$logFolder)) {
        mkdir(self::$logFolder, 0777, true) || throw new \Exception('Log folder ' . self::$logFolder . ' cannot be created', 1);
      }
    }
    $ret = is_dir(self::$logFolder) && is_writable(self::$logFolder);
    return $ret;
  }

  static public function defineLogTag(string $tag)
  {
    if (self::$syslogOpened) {
      closelog();
      self::$syslogOpened = false;
    }
    self::$syslogOpened = openlog($tag, LOG_PID | LOG_CONS, LOG_LOCAL0);
  }

  static public function defineLogFilters(array $areas, int $logLevel)
  {
    self::$areas = $areas;
    self::$minLogLevel = $logLevel;
  }

  static private function getLogFileHandler() 
  {
    if (null==self::$logFileHandler) {
      $fileName = self::$logFolder . '/' . date('Y-m-d') . '.log';
      self::$logFileHandler = fopen($fileName, 'a+');
    }
    return self::$logFileHandler;
  }

  static private function closeLog() 
  {
    if (self::$syslogOpened) {
      closelog();
      self::$syslogOpened = false;
    }
    if (null!=self::$logFileHandler) {
      fflush(self::$logFileHandler);
      fclose(self::$logFileHandler);
      self::$logFileHandler = null;
    }
  }

  static public function log(int $area, int $warningLevel, string $message)
  {
    global $currentURI;

    if (self::startup()) {
      if ($warningLevel >= self::$minLogLevel - 99) {
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
          if (self::$syslogOpened) {
            if ($warningLevel <= YeAPF_LOG_DEBUG)
              $OS_level = LOG_DEBUG;
            elseif ($warningLevel <= YeAPF_LOG_INFO)
              $OS_level = LOG_INFO;
            elseif ($warningLevel <= YeAPF_LOG_NOTICE)
              $OS_level = LOG_NOTICE;
            elseif ($warningLevel <= YeAPF_LOG_WARNING)
              $OS_level = LOG_WARNING;
            elseif ($warningLevel <= YeAPF_LOG_ERR)
              $OS_level = LOG_ERR;
            elseif ($warningLevel <= YeAPF_LOG_CRIT)
              $OS_level = LOG_CRIT;
            elseif ($warningLevel <= YeAPF_LOG_ALERT)
              $OS_level = LOG_ALERT;
            elseif ($warningLevel <= YeAPF_LOG_EMERG)
              $OS_level = LOG_EMERG;
            else
              $OS_level = LOG_INFO;
            syslog($OS_level, $message);
          }

          $fileName = self::$logFolder . '/' . date('Y-m-d') . '.log';

          $fp = self::getLogFileHandler();          
          if ($fp) {
            fwrite($fp, "$preamble $message\n");            
          }
        }
      }
    }
  }
}
