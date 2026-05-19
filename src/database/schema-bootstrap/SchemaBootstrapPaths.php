<?php declare(strict_types=1);

namespace YeAPF\SchemaBootstrap;

final class SchemaBootstrapPaths
{
    public const SUBDIR = '.config/schema-bootstrap';

    public function __construct(
        private readonly string $applicationRoot
    ) {
    }

    public function applicationRoot(): string
    {
        return $this->applicationRoot;
    }

    public function baseDir(): string
    {
        return rtrim($this->applicationRoot, '/') . '/' . self::SUBDIR;
    }

    public function lockFile(): string
    {
        return $this->baseDir() . '/lock';
    }

    public function stateFile(): string
    {
        return $this->baseDir() . '/state.json';
    }

    public function logFile(): string
    {
        return $this->baseDir() . '/bootstrap.log';
    }

    public function completedMarkerFile(): string
    {
        return $this->baseDir() . '/completed.marker';
    }

    public function documentModelsDir(): string
    {
        $candidates = [
            rtrim($this->applicationRoot, '/') . '/database/documentModels',
            rtrim($this->applicationRoot, '/') . '/assets/documentModels',
        ];

        foreach ($candidates as $dir) {
            if (is_dir($dir)) {
                return $dir;
            }
        }

        return $candidates[0];
    }

    public function ensureBaseDir(): void
    {
        if (!is_dir($this->baseDir())) {
            mkdir($this->baseDir(), 0775, true);
        }
    }

    public static function appIdFromRoot(string $applicationRoot): string
    {
        return substr(hash('sha256', realpath($applicationRoot) ?: $applicationRoot), 0, 16);
    }

    public function appId(): string
    {
        return self::appIdFromRoot($this->applicationRoot);
    }

    public function redisStatusKey(): string
    {
        return 'y2:schema-bootstrap:' . $this->appId() . ':status';
    }

    public function redisWorkersKey(): string
    {
        return 'y2:app:' . $this->appId() . ':workers';
    }
}
