<?php
/**
 * YeAPF Lock Manager
 *
 * @file
 */
namespace YeAPF;

define('DEFAULT_LOCK_TTL', 20);

if (!function_exists('sem_get')) {

    function sem_get($key)
    {
        global $CFGCacheFolder;

        return fopen($CFGCacheFolder . '/.sem.' . $key, 'w+');
    }

    function sem_acquire($sem_id)
    {
        return flock($sem_id, LOCK_EX | LOCK_NB);
    }

    function sem_release($sem_id)
    {
        return flock($sem_id, LOCK_UN);
    }
}

define('LOCK_DEBUG', false);

abstract class yLockAbstract
{
    function __construct($sharedMemoryName = '', $destroyLocksAtEnd = true)
    {
        $this->lockMap = [];
    }

    abstract public function lock($lockName, $wait = true, $ttl = DEFAULT_LOCK_TTL);

    abstract public function unlock($lockName);

    abstract public function locked($lockName);

    abstract public function cleanup();

    public function __keyToInt__($key)
    {
        // these chars are the only one could be used as semaphore name
        $keys = ('0123456789-+_.,@!#qwertyuiopasdfghjklzxcvbnm');

        $key = strtolower($key);

        $key = trim($key);
        $ret = 0;
        $p = 1;
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

        _trace("yLock: Converting '$key' to intkey: '$ret'");

        return $ret;
    }
}

class yLockSem extends yLockAbstract
{
    function __construct($sharedMemoryName = 'default', $destroyLocksAtEnd = true)
    {
        $this->lockMap = [];
        $this->destroyLocksAtEnd = $destroyLocksAtEnd;
        $this->sharedMemoryId = self::__keyToInt__($sharedMemoryName);
        _trace('----[yLock]----');
        _trace("yLock: Adquirindo memoria compartilhada: '$sharedMemoryName' $this->sharedMemoryId");

        $this->flagSemaphore = sem_get(
            $this->sharedMemoryId,
            1,
            0666,
            true
        );
        _trace("yLock: sharedMemoryId=$this->sharedMemoryId");
    }

    private function _getLockData($lockId)
    {
        $now = date('U');
        $defaultValue = [
        'ts' => $now,
        'flag' => false,
        'ttl' => DEFAULT_LOCK_TTL,
        ];

        $lockValuePtr = shm_attach($lockId, 256);
        if ($lockValuePtr) {
            $lockValue = shm_get_var($lockValuePtr, 0);
            if (!is_array($lockValue)) {
                $lockValue = $defaultValue;
            }

            _trace('yLock retrieveng lockData: ' . json_encode($lockValue));
            shm_detach($lockValuePtr);
        }

        return $lockValue;
    }

    private function _setLockData($lockId, $lockData)
    {
        $ret = false;
        $lockValuePtr = shm_attach($lockId, 256);
        if ($lockValuePtr) {
            $ret = shm_put_var($lockValuePtr, 0, $lockData);
            $lockValueAux = shm_get_var($lockValuePtr, 0);
            _trace('yLock checking data: ' . json_encode($lockValueAux));
            _trace('yLock saving lockData: ' . json_encode($lockData) . ' ret=' . var_export($ret, true));
            shm_detach($lockValuePtr);
        }
        return $ret;
    }

    function lock($lockName, $wait = true, $ttl = DEFAULT_LOCK_TTL)
    {
        $this->lockMap[$lockName] = true;
        _trace("yLock: begin  lock ('$lockName'); wait=" . intval($wait));
        $lockId = self::__keyToInt__($lockName);
        $ret = false;
        $errCounter = 0;
        do {
            if (sem_acquire($this->flagSemaphore, !$wait)) {
                _trace('yLock: semaphore adquired');
                try {
                    $lockInfo = $this->_getLockData($lockId);
                    if (!$lockInfo['flag'] || date('U') >= $lockInfo['ts'] + $lockInfo['ttl']) {
                        $lockInfo['flag'] = true;
                        $lockInfo['ts'] = date('U');
                        $lockInfo['ttl'] = $ttl;
                        $ret = $this->_setLockData($lockId, $lockInfo);
                    } else {
                        $ret = false;
                    }
                    _trace('yLock: LOCK return ' . var_export($ret, true));
                } catch (Exception $ex) {
                    _trace('yLock: LOCK - Exception ' > $ex->getMessage());
                    $errCounter++;
                } finally {
                    sem_release($this->flagSemaphore);
                    _trace('yLock: semaphore released');
                }
            } else {
                _trace('yLock: semaphore was not available');
            }
            if ($wait && !$ret) {
                sleep(3);
            }
        } while ($wait && !$ret && $errCounter < 2);
        _trace("yLock: end lock('$lockName') = " . json_encode($ret));
        return $ret;
    }

    function unlock($lockName)
    {
        _trace("yLock: begin unlock ('$lockName')");
        $lockId = self::__keyToInt__($lockName);
        $ret = false;
        if (sem_acquire($this->flagSemaphore, false)) {
            _trace('yLock: semaphore adquired');
            try {
                $lockInfo = $this->_getLockData($lockId);
                $lockInfo['flag'] = false;
                $lockInfo['ts'] = date('U') - 100;
                $lockInfo['ttl'] = 0;
                $ret = $this->_setLockData($lockId, $lockInfo);
                if (!empty($this->lockMap[$lockName])) {
                    unset($this->lockMap[$lockName]);
                }
            } catch (Exception $ex) {
                _trace('yLock: UNLOCK - Exception ' > $ex->getMessage());
            } finally {
                sem_release($this->flagSemaphore);
                _trace('yLock: semaphore released');
            }
        }
        _trace("yLock: end unlock ('$lockName') = " . json_encode($ret));
        return $ret;
    }

    function locked($lockName)
    {
        $lockId = self::__keyToInt__($lockName);
        $lockInfo = $this->_getLockData($lockId);
        return $lockInfo['flag'];
    }

    public function cleanup()
    {
        _trace('yLock: Destroying semaphore ' . __CLASS__);
        _trace('yLock: still locked resources: ' . json_encode($this->lockMap));
        foreach ($this->lockMap as $lockName => $lockExists) {
            _trace("yLock: WARNING unlocking $lockName WARNING!");

            $lockId = self::__keyToInt__($lockName);

            $lockInfo = $this->_getLockData($lockId);
            $lockInfo['flag'] = false;
            $lockInfo['ts'] = date('U') - 100;
            $lockInfo['ttl'] = 0;
            $ret = $this->_setLockData($lockId, $lockInfo);
            if (!empty($this->lockMap[$lockName])) {
                unset($this->lockMap[$lockName]);
            }
        }
    }

    public function __destruct()
    {
        if ($this->destroyLocksAtEnd) {
            self::cleanup();
        }
        sem_release($this->flagSemaphore);
        _trace('----[/yLock]----');
    }
}

class yLock
{
    public function lock($lockName, $wait = true, $ttl = DEFAULT_LOCK_TTL)
    {
        $ret = false;
        $lockName = basename($lockName);
        clearstatcache();
        $fileName = sys_get_temp_dir() . '/' . $lockName . '.lock';
        if (file_exists($fileName)) {
            $mt = date('U') - filemtime($fileName);
            $ttl = 60;
            _trace("$fileName em uso $mt segundos+");
            if ($mt && $mt > $ttl) {
                _trace("$fileName liberado forçadamente por estar preso há mais de $ttl segundos");
                unlink($fileName);
            }
        }
        if ($fp = fopen($fileName, 'w+')) {
            $canWrite = flock($fp, LOCK_EX | LOCK_NB);
            if (!$canWrite) {
                _trace('Lock being used');
                fclose($fp);
                $ret = false;
            } else {
                _trace('Locked');
                $informacao = [
                'viaCLI' => (php_sapi_name() === 'cli'),
                'start' => date('U'),
                'PID' => getmypid(),
                ];
                fputs($fp, json_encode($informacao));
                fflush($fp);
                $ret = $lockName;
                $this->lockMap[$ret] = [
                'fp' => $fp,
                'fileName' => $fileName,
                ];
                _trace("Usando trava #$ret = $fileName");
            }
        }
        _trace('ret = ' . json_encode($ret));
        return $ret;
    }

    public function unlock($lockName)
    {
        $lockName = basename($lockName);
        if (!empty($this->lockMap[$lockName])) {
            $fp = $this->lockMap[$lockName]['fp'];
            $fileName = $this->lockMap[$lockName]['fileName'];

            _trace("Liberando trava $fileName");

            if (file_exists($fileName)) {
                flock($fp, LOCK_UN);
                fclose($fp);

                unlink($fileName);
            }

            unset($this->lockMap[$lockName]);
        }
    }

    public function locked($lockName)
    {
        return true;
    }

    public function cleanup()
    {
        if (isset($this->lockMap)) {
            foreach ($this->lockMap as $k => $lockInfo) {
                $this->unlock($k);
            }
        }
    }
}
