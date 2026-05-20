<?php declare(strict_types=1);

namespace YeAPF\SchemaBootstrap;

final class SchemaSessionOrchestrator
{
    private const ADVISORY_LOCK_KEY = 75843021;

    public function __construct(
        private readonly SchemaBootstrapPaths $paths,
        private readonly SchemaBootstrapGate $gate,
        private readonly SchemaBootstrapFilesystemLock $fsLock,
        private readonly SchemaBootstrapRedisStatus $redisStatus,
        private readonly SchemaBootstrapState $state,
        private readonly SchemaBootstrapLogger $logger,
        private readonly SchemaAlignmentService $alignment,
        private readonly RuntimeCatalog $catalog
    ) {
    }

    /**
     * @param \YeAPF\Connection\PersistenceContext $context
     */
    public function run(
        object $context,
        object $pdo,
        bool $force = false
    ): string {
        $this->gate->assertCanRunBootstrap($force);

        if ($force && $this->redisStatus->countActiveWorkers() > 0) {
            $this->logger->append('preflight', 'ok', [
                'action' => 'force_with_active_workers',
                'reason' => 'dev override',
            ]);
        }

        $schemaHash = DocumentModelManifest::hashDirectory($this->paths->documentModelsDir());

        try {
            $this->redisStatus->write(SchemaBootstrapState::PREFLIGHT, ['schemaHash' => $schemaHash]);
            $this->state->write(SchemaBootstrapState::PREFLIGHT, ['schemaHash' => $schemaHash]);
            $this->logger->append('preflight', 'ok', ['action' => 'started', 'schemaHash' => $schemaHash]);

            $this->fsLock->acquire();

            $this->acquireAdvisoryLock($pdo);
            $pdo->query('BEGIN');

            $this->state->write(SchemaBootstrapState::MIGRATING, ['schemaHash' => $schemaHash]);
            $this->redisStatus->write(SchemaBootstrapState::MIGRATING, ['schemaHash' => $schemaHash]);

            $this->alignment->alignAll($context, $this->paths->documentModelsDir(), $pdo);

            $this->state->write(SchemaBootstrapState::CATALOG, ['schemaHash' => $schemaHash]);
            $this->redisStatus->write(SchemaBootstrapState::CATALOG, ['schemaHash' => $schemaHash]);
            $this->catalog->runAll($pdo, $this->logger, ['applicationRoot' => $this->paths->applicationRoot()]);

            $pdo->query('COMMIT');
            $this->releaseAdvisoryLock($pdo);

            $catalogVersion = (string) $this->catalog->count();
            $this->appendCompletedMarker($schemaHash, $catalogVersion);

            $this->fsLock->release();
            $this->redisStatus->write(SchemaBootstrapState::COMPLETED, [
                'schemaHash' => $schemaHash,
                'catalogVersion' => $catalogVersion,
            ]);
            $this->state->write(SchemaBootstrapState::COMPLETED, [
                'schemaHash' => $schemaHash,
                'catalogVersion' => $catalogVersion,
            ]);

            $this->logger->append('completed', 'ok', [
                'schemaHash' => $schemaHash,
                'catalogVersion' => $catalogVersion,
            ]);

            return $schemaHash;
        } catch (\Throwable $e) {
            try {
                $pdo->query('ROLLBACK');
            } catch (\Throwable) {
            }

            try {
                $this->releaseAdvisoryLock($pdo);
            } catch (\Throwable) {
            }

            $this->fsLock->release();
            $this->state->write(SchemaBootstrapState::FAILED, ['reason' => $e->getMessage()]);
            $this->redisStatus->write(SchemaBootstrapState::FAILED, ['reason' => $e->getMessage()]);

            $this->logger->append('failed', 'error', ['reason' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * @param \YeAPF\Connection\PersistenceContext $context
     */
    public static function factory(
        string $applicationRoot,
        object $context,
        ?object $redis = null
    ): self {
        $paths = new SchemaBootstrapPaths($applicationRoot);
        $logger = new SchemaBootstrapLogger($paths);
        $state = new SchemaBootstrapState($paths);
        $fsLock = new SchemaBootstrapFilesystemLock($paths, $logger);
        $redisStatus = new SchemaBootstrapRedisStatus($paths, $redis);
        $gate = new SchemaBootstrapGate($paths, $fsLock, $redisStatus, $state);
        $alignment = new SchemaAlignmentService($logger);
        $catalog = new RuntimeCatalog();

        return new self($paths, $gate, $fsLock, $redisStatus, $state, $logger, $alignment, $catalog);
    }

    public function gate(): SchemaBootstrapGate
    {
        return $this->gate;
    }

    public function catalog(): RuntimeCatalog
    {
        return $this->catalog;
    }

    public function paths(): SchemaBootstrapPaths
    {
        return $this->paths;
    }

    public function logger(): SchemaBootstrapLogger
    {
        return $this->logger;
    }

    private function acquireAdvisoryLock(object $pdo): void
    {
        $sql = 'SELECT pg_advisory_lock(' . self::ADVISORY_LOCK_KEY . ')';
        $pdo->query($sql);
        $this->logger->append('migrate', 'ok', ['action' => 'pg_advisory_lock']);
    }

    private function releaseAdvisoryLock(object $pdo): void
    {
        $sql = 'SELECT pg_advisory_unlock(' . self::ADVISORY_LOCK_KEY . ')';
        $pdo->query($sql);
        $this->logger->append('migrate', 'ok', ['action' => 'pg_advisory_unlock']);
    }

    private function appendCompletedMarker(string $schemaHash, string $catalogVersion): void
    {
        $this->paths->ensureBaseDir();
        $line = gmdate('c') . "\t" . $schemaHash . "\t" . $catalogVersion . "\n";
        file_put_contents($this->paths->completedMarkerFile(), $line, FILE_APPEND | LOCK_EX);
    }
}
