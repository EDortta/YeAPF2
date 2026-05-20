<?php declare(strict_types=1);

namespace YeAPF\SchemaBootstrap;

final class SchemaBootstrapFilesystemLock
{
    /** @var resource|null */
    private $handle = null;

    public function __construct(
        private readonly SchemaBootstrapPaths $paths,
        private readonly SchemaBootstrapLogger $logger
    ) {
    }

    public function isLocked(): bool
    {
        $lockFile = $this->paths->lockFile();
        if (!is_file($lockFile)) {
            return false;
        }

        if ($this->isStaleLock($lockFile)) {
            return false;
        }

        return true;
    }

    public function acquire(): void
    {
        $this->paths->ensureBaseDir();
        $lockFile = $this->paths->lockFile();

        if ($this->isStaleLock($lockFile)) {
            @unlink($lockFile);
        }

        $fp = fopen($lockFile, 'c+');
        if ($fp === false) {
            throw new SchemaBootstrappedException('Cannot open schema bootstrap lock file');
        }

        if (!flock($fp, LOCK_EX | LOCK_NB)) {
            fclose($fp);
            throw new SchemaBootstrappedException('Schema bootstrap lock is held by another process');
        }

        $payload = json_encode([
            'pid' => getmypid(),
            'startedAt' => gmdate('c'),
            'viaCLI' => php_sapi_name() === 'cli',
            'host' => gethostname() ?: 'unknown',
        ], JSON_THROW_ON_ERROR);

        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, $payload);
        fflush($fp);

        $this->handle = $fp;
        $this->logger->append('lock', 'ok', ['action' => 'acquire_fs']);
    }

    public function release(): void
    {
        $lockFile = $this->paths->lockFile();

        if (is_resource($this->handle)) {
            flock($this->handle, LOCK_UN);
            fclose($this->handle);
            $this->handle = null;
        }

        if (is_file($lockFile)) {
            unlink($lockFile);
        }

        $this->logger->append('lock', 'ok', ['action' => 'release_fs']);
    }

    private function isStaleLock(string $lockFile): bool
    {
        $raw = @file_get_contents($lockFile);
        if ($raw === false || $raw === '') {
            return true;
        }

        $info = json_decode($raw, true);
        if (!is_array($info) || !isset($info['pid'])) {
            return true;
        }

        $pid = (int) $info['pid'];
        if ($pid <= 0) {
            return true;
        }

        if (function_exists('posix_kill')) {
            return !posix_kill($pid, 0);
        }

        return false;
    }
}
