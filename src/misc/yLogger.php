<?php declare(strict_types=1);

namespace YeAPF;

class yLogger
{
  use \YeAPF\Assets;

  static private $tag = 'YeAPF';
  static private $syslogOpened = false;

  // log
  static private $logFolder = null;

  static private $lastLogSourceUsage = null;

  static private $activeLogAreas = [];

  static private $minLogLevel = YeAPF_LOG_WARNING;

  static private $logFileHandler = null;

  // trace
  static private $traceStartMicrotime = null;

  static private $lastTraceSourceUsage = null;

  static private $activeTraceAreas = [];

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

  static public function defineLogFilters(int $logLevel, array $activeLogAreas = [])
  {
    self::$activeLogAreas = $activeLogAreas;
    self::$minLogLevel = $logLevel;

    self::$activeTraceAreas = $activeLogAreas;
    self::$minTraceLevel = $logLevel;
  }

  static public function addLogArea(int $area)
  {
    $equalAreas = (count(self::$activeLogAreas) === count(array_intersect(self::$activeLogAreas, self::$activeTraceAreas))) && (count(self::$activeLogAreas) === count(self::$activeTraceAreas));

    if (!in_array($area, self::$activeLogAreas)) {
      self::$activeLogAreas[] = $area;
    }

    if ($equalAreas) {
      self::$activeTraceAreas = self::$activeLogAreas;
    }
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

  static public function markStartupTimestamp()
  {
    self::$traceStartMicrotime = microtime(true);
  }

  static public function defineTraceFilters(int $traceLevel, array $activeTraceAreas = [])
  {
    if (!empty($activeTraceAreas)) {
      self::$activeTraceAreas = $activeTraceAreas;
    }
    self::$minTraceLevel = $traceLevel;
  }

  static public function addTraceArea(int $area)
  {
    if (!in_array($area, self::$activeTraceAreas)) {
      self::$activeTraceAreas[] = $area;
    }
  }

  static public function setTraceDescriptor(string $descriptor)
  {
    if (null == self::$traceStartMicrotime)
      self::markStartupTimestamp();
    self::$traceDetails['descriptor'] = $descriptor;
  }

  static public function setTraceDetails($uri = null, $method = null, $payload = null, $headers = null, $httpCode = null, $return = null, $server = null, $cookie = null)
  {
    if (null == self::$traceStartMicrotime)
      self::markStartupTimestamp();
    self::$traceDetails['url'] = $uri ?? (self::$traceDetails['url'] ?? null);
    self::$traceDetails['method'] = $method ?? (self::$traceDetails['method'] ?? null);
    self::$traceDetails['payload'] = $payload ?? (self::$traceDetails['payload'] ?? null);
    self::$traceDetails['headers'] = $headers ?? (self::$traceDetails['headers'] ?? null);
    self::$traceDetails['httpCode'] = $httpCode ?? (self::$traceDetails['httpCode'] ?? null);
    self::$traceDetails['return'] = $return ?? (self::$traceDetails['return'] ?? null);
    self::$traceDetails['server'] = $server ?? (self::$traceDetails['server'] ?? null);
    self::$traceDetails['cookie'] = $cookie ?? (self::$traceDetails['cookie'] ?? null);
  }

  static private function _traceDetail($d)
  {
    if (!empty(self::$traceDetails[$d])) {
      $lineStart = mb_strtoupper("$d") . str_repeat('.', 12 - strlen($d));
      if (is_array(self::$traceDetails[$d])) {
        foreach (self::$traceDetails[$d] as $k => $v) {
          fwrite(self::$traceFileHandler, $lineStart . "$k: $v\n");
          $lineStart = str_repeat(' ', 12);
        }
      } else
        fwrite(self::$traceFileHandler, $lineStart . self::$traceDetails[$d] . "\n");
    }
  }

  static private function getTraceFileHandler()
  {
    if (null == self::$traceStartMicrotime)
      self::markStartupTimestamp();
    if (null == self::$traceFileHandler) {
      if (!is_dir(self::$logFolder . '/trace')) {
        mkdir(self::$logFolder . '/trace', 0777, true) || throw new \Exception('Trace folder ' . self::$logFolder . '/trace cannot be created', 1);
      }
      if (is_writable(self::$logFolder . '/trace')) {
        $fileName = self::$logFolder . '/trace/' . date('Y-m-d-H-') . generateShortUniqueId() . '.trace';
        self::$traceFileHandler = fopen($fileName, 'a+');
        if (!empty(self::$traceDetails['descriptor'])) {
          fwrite(self::$traceFileHandler, self::$traceDetails['descriptor'] . "\n");
        }
        fwrite(self::$traceFileHandler, 'Started at ' . date('Y-m-d H:i:s') . "\n");
        $details = ['method', 'url', 'payload', 'headers'];
        foreach ($details as $d) {
          self::_traceDetail($d);
        }
        fwrite(self::$traceFileHandler, str_repeat('-', 80) . "\n");
      }
    }
    return self::$traceFileHandler;
  }

  static public function closeTrace()
  {
    if (null != self::$traceFileHandler) {
      fwrite(self::$traceFileHandler, str_repeat('-', 80) . "\n");
      fwrite(self::$traceFileHandler, 'Ended at ' . date('Y-m-d H:i:s') . "\n");
      $details = ['httpCode', 'return'];
      foreach ($details as $d) {
        self::_traceDetail($d);
      }
      $consumedMicrotime = microtime(true) - self::$traceStartMicrotime;
      fwrite(self::$traceFileHandler, 'Consumed ' . $consumedMicrotime . ' seconds' . "\n");
      self::$traceStartMicrotime = null;

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
