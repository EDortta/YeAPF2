<?php declare(strict_types=1);

namespace YeAPF\SchemaBootstrap;

final class SchemaBootstrapState
{
    public const IDLE = 'idle';
    public const PREFLIGHT = 'preflight';
    public const MIGRATING = 'migrating';
    public const CATALOG = 'catalog';
    public const COMPLETED = 'completed';
    public const FAILED = 'failed';

    public function __construct(
        private readonly SchemaBootstrapPaths $paths
    ) {
    }

    public function read(): string
    {
        if (!is_file($this->paths->stateFile())) {
            return self::IDLE;
        }

        $raw = file_get_contents($this->paths->stateFile());
        if ($raw === false || $raw === '') {
            return self::IDLE;
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded) || !isset($decoded['state'])) {
            return self::IDLE;
        }

        return (string) $decoded['state'];
    }

    public function write(string $state, array $extra = []): void
    {
        $this->paths->ensureBaseDir();
        $payload = array_merge([
            'state' => $state,
            'updatedAt' => gmdate('c'),
            'pid' => getmypid(),
            'host' => gethostname() ?: 'unknown',
        ], $extra);

        file_put_contents(
            $this->paths->stateFile(),
            json_encode($payload, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT)
        );
    }
}
