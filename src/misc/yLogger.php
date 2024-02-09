<?php declare(strict_types=1);

namespace YeAPF;

class yLogger
{
  use \YeAPF\Assets;

  static private $tag = 'YeAPF';
  static private $syslogOpened = false;
  static private $logFolder = null;
  static private $lastLogSourceUsage = null;
  static private $logAreas = [];
  static private $minLogLevel = YeAPF_LOG_WARNING;
  static private $logFileHandler = null;
  static private $lastTraceSourceUsage = null;
  static private $traceAreas = [];
  static private $minTraceLevel = YeAPF_LOG_WARNING;
  static private $traceFileHandler = null;
  static private $traceDetails = [];

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

  /**
   * This function initializes the log folder if it is not already set,
   * and checks if the log folder is writable.
   *
   * @throws \Exception Log folder cannot be created
   * @return bool Returns true if the log folder is writable, false otherwise
   */
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

  // Log Tag and Filters

  static public function defineLogTag(string $tag)
  {
    self::$tag = $tag;
    if (self::$syslogOpened) {
      closelog();
      self::$syslogOpened = false;
    }
    self::$syslogOpened = openlog($tag, LOG_PID | LOG_CONS, LOG_LOCAL0);
  }

  static public function defineLogFilters(array $logAreas, int $logLevel)
  {
    self::$logAreas = $logAreas;
    self::$minLogLevel = $logLevel;

    self::$traceAreas = $logAreas; 
    self::$minTraceLevel = $logLevel;
  }

  // Log file functions

  static private function getLogFileHandler()
  {
    if (null == self::$logFileHandler) {
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
    if (null != self::$logFileHandler) {
      fflush(self::$logFileHandler);
      fclose(self::$logFileHandler);
      self::$logFileHandler = null;
    }
  }

  // Log
  static public function log(int $area, int $warningLevel, string $message)
  {
    global $currentURI;

    if (self::startup()) {
      if ($warningLevel >= self::$minLogLevel - 99) {
        $dbg = debug_backtrace();
        $time = date('h:i:s ');
        $preamble = "$time";
        if (self::$lastLogSourceUsage != $dbg[1]['file']) {
          self::$lastLogSourceUsage = $dbg[1]['file'];
          $preamble .= self::$lastLogSourceUsage . "---\n$time";
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
          
          $fp = self::getLogFileHandler();
          if ($fp) {
            fwrite($fp, "$preamble $message\n");
          }
        }
      }
    }
  }

  // Trace

  static public function defileTraceFilters(array $traceAreas, int $traceLevel)
  {
    self::$traceAreas = $traceAreas;
    self::$minTraceLevel = $traceLevel;
  }

  static public function setTraceHeader(string $header)
  {
    self::$traceDetails['header'] = $header;
  }

  static public function setTraceDetails($uri = null, $method = null, $payload = null, $headers = null, $httpCode = null, $jsonData = null)
  {
    self::$traceDetails['url'] = $uri ?? (self::$traceDetails['url'] ?? null);
    self::$traceDetails['method'] = $method ?? (self::$traceDetails['method'] ?? null);
    self::$traceDetails['payload'] = $payload ?? (self::$traceDetails['payload'] ?? null);
    self::$traceDetails['headers'] = $headers ?? (self::$traceDetails['headers'] ?? null);
    self::$traceDetails['httpCode'] = $httpCode ?? (self::$traceDetails['httpCode'] ?? null);
    self::$traceDetails['jsonData'] = $jsonData ?? (self::$traceDetails['jsonData'] ?? null);
  }

  static private function getTraceFileHandler()
  {
    if (null == self::$traceFileHandler) {
      $fileName = self::$logFolder . '/' . date('Y-m-d@') . generateShortUniqueId() . '.trace';
      self::$traceFileHandler = fopen($fileName, 'a+');
      if (!empty(self::$traceDetails['header'])) {
        fwrite(self::$traceFileHandler, self::$traceDetails['header'] . "\n\n");
      }
      fwrite(self::$traceFileHandler, 'Started at ' . date('Y-m-d H:i:s') . "\n\n");
      $details = ['method', 'url', 'payload', 'headers'];
      foreach ($details as $d) {
        if (!empty(self::$traceDetails[$d])) {
          fwrite(self::$traceFileHandler, "$d: " . self::$traceDetails[$d] . "\n");
        }
      }
      fwrite(self::$traceFileHandler, str_repeat('-', 80) . "\n\n");
    }
    return self::$traceFileHandler;
  }

  static public function closeTrace()
  {
    if (null != self::$traceFileHandler) {
      fwrite(self::$traceFileHandler, str_repeat('-', 80) . "\n\n");
      $details = ['httpCode', 'jsonData'];
      foreach ($details as $d) {
        if (!empty(self::$traceDetails[$d])) {
          fwrite(self::$traceFileHandler, "$d: " . self::$traceDetails[$d] . "\n");
        }
      }
      fflush(self::$traceFileHandler);
      fclose(self::$traceFileHandler);
      self::$traceFileHandler = null;
    }
  }

  static public function trace(int $area, int $warningLevel, string $message)
  {
    if ($warningLevel >= self::$minTraceLevel - 99) {
      $dbg = debug_backtrace();
      $time = date('h:i:s ');
      $preamble = "$time";
      if (self::$lastTraceSourceUsage != $dbg[1]['file']) {
        self::$lastTraceSourceUsage = $dbg[1]['file'];
        $preamble .= self::$lastTraceSourceUsage . "---\n$time";        
      }      
      $preamble .= '  ' . str_pad(' ' . $dbg[1]['line'], 4, ' ', STR_PAD_LEFT) . ': ';
      $message = str_replace("\n", "\n    ", $message);
      $fp = self::getTraceFileHandler();
      if ($fp) {
        fwrite($fp, "$preamble $message\n");        
      }
    }
  }
}
