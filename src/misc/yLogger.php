<?php declare(strict_types=1);

namespace YeAPF;

class yLogger
{
  use \YeAPF\Assets;

  static private $tag = 'YeAPF';
  static private $syslogOpened = false;
  static private $serverInfo = [];

  // log
  static private $logFacility = YeAPF_LOG_USING_FILE;

  static private $logFolder = null;

  static private $lastLogSourceUsage = null;

  static private $activeLogAreas = [];

  static private $minLogLevel = YeAPF_LOG_WARNING;

  static private $logFileHandler = null;

  // trace
  static private $traceBuffer = [];

  static private $useTraceBuffer = true;

  static private $traceToLog = false;

  static private $traceStartMicrotime = null;

  static private $lastTraceSourceUsage = null;

  static private $activeTraceAreas = [];

  static private $minTraceLevel = YeAPF_LOG_WARNING;

  static private $traceFileHandler = null;

  static private $traceFileName = null;

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

  static public function setLogTags(array|string $serverInfo, ?string $serverValue = null)
  {
    $validTags = [
      YeAPF_LOG_TAG_SERVER,
      YeAPF_LOG_TAG_SERVICE,
      YeAPF_LOG_TAG_CLIENT,
      YeAPF_LOG_TAG_USER,
      YeAPF_LOG_TAG_USERID,
      YeAPF_LOG_TAG_REQUEST_TIME,
      YeAPF_LOG_TAG_REQUEST,
      YeAPF_LOG_TAG_RESULT,
      YeAPF_LOG_TAG_RESPONSE_SIZE,
      YeAPF_LOG_TAG_RESPONSE_TIME,
      YeAPF_LOG_TAG_RESPONSE_ERROR,
      YeAPF_LOG_TAG_REFERER,
      YeAPF_LOG_TAG_USERAGENT,
    ];
    if (is_string($serverInfo)) {
      if (in_array($serverInfo, $validTags)) {
        self::$serverInfo[$serverInfo] = $serverValue;
      }
    } else {
      foreach ($serverInfo as $tag => $value) {
        if (in_array($tag, $validTags)) {
          self::$serverInfo[$tag] = $value;
        }
      }
    }
  }

  static public function getLogTags(string $tag)
  {
    $ret = '-';
    if (isset(self::$serverInfo[$tag])) {
      $ret = self::$serverInfo[$tag];
      if (is_string($ret)) {
        if (strpos($ret, ' ') !== false) {
          $ret = '"' . $ret . '"';
        }
      }
    }
    return $ret;
  }

  static public function setLogFacility(int $facility)
  {
    $validBits = YeAPF_LOG_USING_FILE | YeAPF_LOG_USING_DB | YeAPF_LOG_USING_CONSOLE | YeAPF_LOG_USING_SYSLOG;
    if (($facility & $validBits) == $facility) {
      self::$logFacility = $facility;
    } else {
      throw new \Exception('Invalid log facility');      
    }
  }

  static public function getLogFacility(): int
  {
    return self::$logFacility;
  }

  static public function logFacilityEnabled(int $facility): bool
  {
    return (self::$logFacility & $facility) == $facility;
  }

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

  static public function closeLog()
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
  static public function syslog(int|string $area = 0, int $minLogLevel = YeAPF_LOG_DEBUG, string $message = '')
  {
    if (self::startup()) {
      if ($minLogLevel >= self::$minLogLevel - 99) {
        if (0 === $area || in_array($area, self::$activeLogAreas)) {
          if (self::$syslogOpened) {
            if ($minLogLevel <= YeAPF_LOG_DEBUG)
              $OS_level = LOG_DEBUG;
            elseif ($minLogLevel <= YeAPF_LOG_INFO)
              $OS_level = LOG_INFO;
            elseif ($minLogLevel <= YeAPF_LOG_NOTICE)
              $OS_level = LOG_NOTICE;
            elseif ($minLogLevel <= YeAPF_LOG_WARNING)
              $OS_level = LOG_WARNING;
            elseif ($minLogLevel <= YeAPF_LOG_ERROR)
              $OS_level = LOG_ERR;
            elseif ($minLogLevel <= YeAPF_LOG_CRITICAL)
              $OS_level = LOG_CRIT;
            elseif ($minLogLevel <= YeAPF_LOG_ALERT)
              $OS_level = LOG_ALERT;
            elseif ($minLogLevel <= YeAPF_LOG_EMERG)
              $OS_level = LOG_EMERG;
            else
              $OS_level = LOG_INFO;

            $message = str_replace("\n", ' ', $message);
            $message = trim($message);
            if (strpos($message, ' ') !== false) {
              $message = '"' . $message . '"';
            }
            $sysLogMessage = ''
              // . date('Y-m-d\TH:i:sP') . ' '
              // . self::getLogTags(YeAPF_LOG_TAG_SERVER) . ' '
              // . self::getLogTags(YeAPF_LOG_TAG_SERVICE) . ': '
              . self::getLogTags(YeAPF_LOG_TAG_CLIENT) . ' '
              . self::getLogTags(YeAPF_LOG_TAG_USER) . ' '
              . self::getLogTags(YeAPF_LOG_TAG_USERID) . ' '
              . self::getLogTags(YeAPF_LOG_TAG_REQUEST_TIME) . ' '
              . self::getLogTags(YeAPF_LOG_TAG_REQUEST) . ' '
              . self::getLogTags(YeAPF_LOG_TAG_RESULT) . ' '
              . self::getLogTags(YeAPF_LOG_TAG_RESPONSE_SIZE) . ' '
              . self::getLogTags(YeAPF_LOG_TAG_REFERER) . ' '
              . self::getLogTags(YeAPF_LOG_TAG_USERAGENT) . ' '
              . self::getLogTags(YeAPF_LOG_TAG_RESPONSE_TIME) . 'ms '
              . $message;
            syslog($OS_level, $sysLogMessage);
          }
        }
      }
    }
  }

  static public function log(int|string $area, int $minLogLevel, string $message)
  {
    global $currentURI;

    if (self::startup()) {
      if ($minLogLevel >= self::$minLogLevel - 99) {
        if (0 === $area || in_array($area, self::$activeLogAreas)) {
          $dbg = debug_backtrace();
          $time = date('h:i:s ');
          $preamble = "$time";
          if (self::$lastLogSourceUsage != $dbg[1]['file']) {
            self::$lastLogSourceUsage = $dbg[1]['file'];
            $preamble .= '[' . self::$lastLogSourceUsage . "] ------\n$time";
            // echo json_encode($dbg[1],JSON_PRETTY_PRINT);
          }
          if ($currentURI > '') {
            $preamble .= '  ' . str_pad(' ' . $dbg[1]['line'], 4, ' ', STR_PAD_LEFT) . ': [' . $currentURI . '] ';
          } else {
            $preamble .= '  ' . str_pad(' ' . $dbg[1]['line'], 4, ' ', STR_PAD_LEFT) . ': ';
          }

          $message = str_replace("\n", ' ', $message);
          if (trim($message) > '') {
            if (self::logFacilityEnabled(YeAPF_LOG_USING_FILE)) {
              $fp = self::getLogFileHandler();
              if ($fp) {
                fwrite($fp, "$preamble $message\n");
              }
            }

            if (self::logFacilityEnabled(YeAPF_LOG_USING_SYSLOG)) {
              self::syslog($area, $minLogLevel, $message);
            }

            if (self::logFacilityEnabled(YeAPF_LOG_USING_CONSOLE)) {
              echo "$preamble $message\n";
            }
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

  static public function defineTraceFilters(?int $traceLevel = null, array $activeTraceAreas = [], ?bool $bufferedOutput = null, ?bool $traceToLog = null)
  {
    if (!empty($activeTraceAreas)) {
      self::$activeTraceAreas = $activeTraceAreas;
    }
    if (null !== $traceLevel)
      self::$minTraceLevel = $traceLevel;
    if (null !== $bufferedOutput)
      self::$useTraceBuffer = $bufferedOutput;
    if (null !== $traceToLog)
      self::$traceToLog = $traceToLog;
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
          if (is_object($v))
            $v = json_encode($v);
          fwrite(self::$traceFileHandler, $lineStart . "$k: $v\n");
          $lineStart = str_repeat(' ', 12);
        }
      } else
        fwrite(self::$traceFileHandler, $lineStart . self::$traceDetails[$d] . "\n");
    }
  }

  static private function _setTraceFilename()
  {
    if (null == self::$traceFileName) {
      $fileName = self::$logFolder . '/trace/' . date('Y-m-d-H-') . generateShortUniqueId() . '.trace';
      self::$traceFileName = $fileName;
    }
    return self::$traceFileName;
  }

  static public function getTraceFilename()
  {
    return self::$traceFileName;
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
        $fileName = self::_setTraceFilename();
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

  static public function closeTrace($flushBuffer = false)
  {
    if ($flushBuffer || !self::$useTraceBuffer) {
      $fp = self::getTraceFileHandler();
      if ($fp) {
        foreach (self::$traceBuffer as $b) {
          fwrite($fp, "$b\n");
        }
        self::$traceBuffer = [];
        fwrite($fp, str_repeat('-', 80) . "\n");
        fwrite($fp, 'Ended at ' . date('Y-m-d H:i:s') . "\n");
        $details = ['httpCode', 'return'];
        foreach ($details as $d) {
          self::_traceDetail($d);
        }
        $consumedMicrotime = microtime(true) - self::$traceStartMicrotime;
        fwrite($fp, 'Consumed ' . $consumedMicrotime . ' seconds' . "\n");
        self::$traceStartMicrotime = null;

        fflush($fp);
        fclose($fp);
        self::$traceFileHandler = null;
        self::$traceFileName = null;
      }
    }
  }

  static public function trace(int $area, int $warningLevel, string $message)
  {
    if ($warningLevel >= self::$minTraceLevel - 99) {
      if (0 === $area || in_array($area, self::$activeTraceAreas)) {
        self::_setTraceFilename();

        $dbg = debug_backtrace();
        $time = date('h:i:s ');
        $preamble = "$time";
        if (self::$lastTraceSourceUsage != $dbg[1]['file']) {
          self::$lastTraceSourceUsage = $dbg[1]['file'];
          $preamble .= '[' . self::$lastTraceSourceUsage . "] ------\n$time";
        }
        $preamble .= '  ' . str_pad(' ' . $dbg[1]['line'], 5, ' ', STR_PAD_LEFT) . ': ';
        $formattedMessage = str_replace("\n", "\n    ", $message);
        if (self::$useTraceBuffer) {
          self::$traceBuffer[] = "$preamble $formattedMessage";
        } else {
          $fp = self::getTraceFileHandler();
          if ($fp) {
            fwrite($fp, "$preamble $formattedMessage\n");
          }
        }

        if (self::$traceToLog) {
          self::log($area, $warningLevel, $message);
        }
      }
    }
  }
}
