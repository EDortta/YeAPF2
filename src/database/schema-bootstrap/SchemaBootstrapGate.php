<?php declare(strict_types=1);

namespace YeAPF\SchemaBootstrap;

final class SchemaBootstrapGate
{
    private const BLOCKING_REDIS_STATES = [
        SchemaBootstrapState::PREFLIGHT,
        SchemaBootstrapState::MIGRATING,
        SchemaBootstrapState::CATALOG,
    ];

    public function __construct(
        private readonly SchemaBootstrapPaths $paths,
        private readonly SchemaBootstrapFilesystemLock $fsLock,
        private readonly SchemaBootstrapRedisStatus $redisStatus,
        private readonly SchemaBootstrapState $state
    ) {
    }

    /**
     * Called before serving traffic or starting bootstrap CLI.
     *
     * @throws SchemaBootstrappedException
     */
    public function assertCanStart(bool $allowPendingBootstrap = false): void
    {
        $redis = $this->redisStatus->read();
        if ($redis !== null) {
            $redisState = (string) ($redis['state'] ?? '');
            if (in_array($redisState, self::BLOCKING_REDIS_STATES, true)) {
                throw new SchemaBootstrappedException(
                    'Schema bootstrap in progress (redis state=' . $redisState . ')'
                );
            }
        }

        if ($this->fsLock->isLocked()) {
            throw new SchemaBootstrappedException(
                'Schema bootstrap lock file is active; quiesce workers and retry'
            );
        }

        if ($allowPendingBootstrap) {
            return;
        }

        $currentHash = DocumentModelManifest::hashDirectory($this->paths->documentModelsDir());
        $lastHash = $this->readLastCompletedSchemaHash();

        if ($lastHash !== null && $lastHash !== $currentHash) {
            throw new SchemaBootstrappedException(
                'Document models changed since last bootstrap; run y2-schema-bootstrap.php'
            );
        }

        if ($lastHash === null && is_dir($this->paths->documentModelsDir())) {
            throw new SchemaBootstrappedException(
                'Schema bootstrap never completed; run y2-schema-bootstrap.php'
            );
        }
    }

    /**
     * @throws SchemaBootstrappedException
     */
    public function assertCanRunBootstrap(bool $force = false): void
    {
        $redis = $this->redisStatus->read();
        if ($redis !== null) {
            $redisState = (string) ($redis['state'] ?? '');
            if (in_array($redisState, self::BLOCKING_REDIS_STATES, true)) {
                throw new SchemaBootstrappedException(
                    'Bootstrap already running (redis state=' . $redisState . ')'
                );
            }
        }

        if ($this->fsLock->isLocked()) {
            throw new SchemaBootstrappedException('Bootstrap lock file already exists');
        }

        $workers = $this->redisStatus->countActiveWorkers();
        if ($workers > 0 && !$force) {
            throw new SchemaBootstrappedException(
                "Active workers detected ($workers); quiesce all YeAPF instances or use --force in dev"
            );
        }
    }

    public function readLastCompletedSchemaHash(): ?string
    {
        $marker = $this->paths->completedMarkerFile();
        if (!is_file($marker)) {
            return null;
        }

        $lines = file($marker, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false || $lines === []) {
            return null;
        }

        $last = trim((string) end($lines));
        $parts = preg_split('/\t+/', $last);
        if (!is_array($parts) || count($parts) < 2) {
            return null;
        }

        return $parts[1];
    }
}
