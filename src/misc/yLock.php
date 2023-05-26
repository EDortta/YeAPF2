<?php
namespace YeAPF;
define('default_lock_ttl', 20);

if (!function_exists('sem_get')) {
  // compatibilidade com Windows e Bitnami
  function sem_get($key) {
    global $CFGCacheFolder;
    return fopen($CFGCacheFolder . '/.sem.' . $key, 'w+');
  }
  function sem_acquire($sem_id) {
    return flock($sem_id, LOCK_EX | LOCK_NB);
  }
  function sem_release($sem_id) {
    return flock($sem_id, LOCK_UN);
  }
}

define("LOCK_DEBUG", false);

abstract class yLockAbstract {
  function __construct($sharedMemoryName = '', $destroyLocksAtEnd = true) {
    $this->lockMap = [];
  }

  abstract public function lock($lockName, $wait = true, $ttl = default_lock_ttl);
  abstract public function unlock($lockName);
  abstract public function locked($lockName);
  abstract public function cleanup();

  public function __keyToInt__($key) {
    // these chars are the only one could be used as semaphore name
    $keys = ('0123456789-+_.,@!#qwertyuiopasdfghjklzxcvbnm');

    $key = strtolower($key);

    $key = trim($key);
    $ret = 0;
    $p   = 1;
    for ($i = 0; $i < strlen($key); $i++) {
      $c = substr($key, $i, 1);
      $c = strpos(" $keys", $c);
      if ($c > 0) {
        $x = $c * ($i + 1) * $p;
        // echo "$c (".$x.") <br>";
        $ret += $x;
        $p *= 2;
      }
    }

    _log("yLock: Converting '$key' to intkey: '$ret'");

    return $ret;
  }

}

class yLockSem extends yLockAbstract {
  function __construct($sharedMemoryName = 'default', $destroyLocksAtEnd = true) {
    $this->lockMap           = [];
    $this->destroyLocksAtEnd = $destroyLocksAtEnd;
    $this->sharedMemoryId    = self::__keyToInt__($sharedMemoryName);
    _log("----[yLock]----");
    _log("yLock: Adquirindo memoria compartilhada: '$sharedMemoryName' $this->sharedMemoryId");

    $this->flagSemaphore = sem_get(
      $this->sharedMemoryId,
      1,
      0666,
      true);
    _log("yLock: sharedMemoryId=$this->sharedMemoryId");
  }

  private function _getLockData($lockId) {
    $now          = date("U");
    $defaultValue = [
      'ts'   => $now,
      'flag' => false,
      'ttl'  => default_lock_ttl,
    ];

    $lockValuePtr = shm_attach($lockId, 256);
    if ($lockValuePtr) {

      $lockValue = shm_get_var($lockValuePtr, 0);
      if (!is_array($lockValue)) {
        $lockValue = $defaultValue;
      }

      _log("yLock retrieveng lockData: " . json_encode($lockValue));
      shm_detach($lockValuePtr);
    }

    return $lockValue;
  }

  private function _setLockData($lockId, $lockData) {
    $ret          = false;
    $lockValuePtr = shm_attach($lockId, 256);
    if ($lockValuePtr) {
      $ret          = shm_put_var($lockValuePtr, 0, $lockData);
      $lockValueAux = shm_get_var($lockValuePtr, 0);
      _log("yLock checking data: " . json_encode($lockValueAux));
      _log("yLock saving lockData: " . json_encode($lockData) . " ret=" . var_export($ret, true));
      shm_detach($lockValuePtr);
    }
    return $ret;
  }

  function lock($lockName, $wait = true, $ttl = default_lock_ttl) {
    $this->lockMap[$lockName] = true;
    _log("yLock: begin  lock ('$lockName'); wait=" . intval($wait));
    $lockId     = self::__keyToInt__($lockName);
    $ret        = false;
    $errCounter = 0;
    do {
      if (sem_acquire($this->flagSemaphore, !$wait)) {
        _log("yLock: semaphore adquired");
        try {
          $lockInfo = $this->_getLockData($lockId);
          if (!$lockInfo['flag'] || date("U") >= $lockInfo['ts'] + $lockInfo['ttl']) {
            $lockInfo['flag'] = true;
            $lockInfo['ts']   = date('U');
            $lockInfo['ttl']  = $ttl;
            $ret              = $this->_setLockData($lockId, $lockInfo);
          } else {
            $ret = false;
          }
          _log("yLock: LOCK return " . var_export($ret, true));
        } catch (Exception $ex) {
          _log("yLock: LOCK - Exception " > $ex->getMessage());
          $errCounter++;
        } finally {
          sem_release($this->flagSemaphore);
          _log("yLock: semaphore released");
        }
      } else {
        _log("yLock: semaphore was not available");
      }
      if ($wait && !$ret) {
        sleep(3);
      }
    } while ($wait && !$ret && $errCounter < 2);
    _log("yLock: end lock('$lockName') = " . json_encode($ret));
    return $ret;
  }

  function unlock($lockName) {
    _log("yLock: begin unlock ('$lockName')");
    $lockId = self::__keyToInt__($lockName);
    $ret    = false;
    if (sem_acquire($this->flagSemaphore, false)) {
      _log("yLock: semaphore adquired");
      try {
        $lockInfo         = $this->_getLockData($lockId);
        $lockInfo['flag'] = false;
        $lockInfo['ts']   = date('U') - 100;
        $lockInfo['ttl']  = 0;
        $ret              = $this->_setLockData($lockId, $lockInfo);
        if (!empty($this->lockMap[$lockName])) {
          unset($this->lockMap[$lockName]);
        }

      } catch (Exception $ex) {
        _log("yLock: UNLOCK - Exception " > $ex->getMessage());
      } finally {
        sem_release($this->flagSemaphore);
        _log("yLock: semaphore released");
      }
    }
    _log("yLock: end unlock ('$lockName') = " . json_encode($ret));
    return $ret;
  }

  function locked($lockName) {
    $lockId   = self::__keyToInt__($lockName);
    $lockInfo = $this->_getLockData($lockId);
    return $lockInfo['flag'];
  }

  public function cleanup() {

    _log("yLock: Destroying semaphore " . __CLASS__);
    _log("yLock: still locked resources: " . json_encode($this->lockMap));
    foreach ($this->lockMap as $lockName => $lockExists) {
      _log("yLock: WARNING unlocking $lockName WARNING!");

      $lockId = self::__keyToInt__($lockName);

      $lockInfo         = $this->_getLockData($lockId);
      $lockInfo['flag'] = false;
      $lockInfo['ts']   = date('U') - 100;
      $lockInfo['ttl']  = 0;
      $ret              = $this->_setLockData($lockId, $lockInfo);
      if (!empty($this->lockMap[$lockName])) {
        unset($this->lockMap[$lockName]);
      }

    }

  }

  public function __destruct() {
    if ($this->destroyLocksAtEnd) {
      self::cleanup();
    }
    sem_release($this->flagSemaphore);
    _log("----[/yLock]----");
  }

}

class yLock {
  public function lock($lockName, $wait = true, $ttl = default_lock_ttl) {
    $ret      = false;
    $lockName = basename($lockName);
    clearstatcache();
    $fileName = sys_get_temp_dir() . '/' . $lockName . ".lock";
    if (file_exists($fileName)) {
      $mt  = date("U") - filemtime($fileName);
      $ttl = 60;
      _log("$fileName em uso $mt segundos+");
      if ($mt && $mt > $ttl) {
        _log("$fileName liberado forçadamente por estar preso há mais de $ttl segundos");
        unlink($fileName);
      }
    }
    if ($fp = fopen($fileName, 'w+')) {
      $canWrite = flock($fp, LOCK_EX | LOCK_NB);
      if (!$canWrite) {
        _log("Lock being used");
        fclose($fp);
        $ret = false;
      } else {
        _log("Locked");
        $informacao = [
          'viaCLI' => (php_sapi_name() === "cli"),
          'start'  => date('U'),
          'PID'    => getmypid(),
        ];
        fputs($fp, json_encode($informacao));
        fflush($fp);
        $ret                 = $lockName;
        $this->lockMap[$ret] = [
          'fp'       => $fp,
          'fileName' => $fileName,
        ];
        _log("Usando trava #$ret = $fileName");
      }
    }
    _log("ret = ".json_encode($ret));
    return $ret;
  }

  public function unlock($lockName) {
    $lockName = basename($lockName);
    if (!empty($this->lockMap[$lockName])) {
      $fp       = $this->lockMap[$lockName]['fp'];
      $fileName = $this->lockMap[$lockName]['fileName'];

      _log("Liberando trava $fileName");

      if(file_exists($fileName))
      {

             flock($fp, LOCK_UN);
             fclose($fp);

             unlink($fileName);
      }


      unset($this->lockMap[$lockName]);
    }
  }

  public function locked($lockName) {
    return true;
  }

  public function cleanup() {
    if (isset($this->lockMap)) {
      foreach ($this->lockMap as $k => $lockInfo) {
        $this->unlock($k);
      }
    }
  }
}