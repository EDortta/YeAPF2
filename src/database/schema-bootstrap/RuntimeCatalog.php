<?php declare(strict_types=1);

namespace YeAPF\SchemaBootstrap;

final class RuntimeCatalog
{
    /** @var list<array{name:string,callable:callable}> */
    private array $entries = [];

    public function register(string $name, callable $callable): void
    {
        $this->entries[] = ['name' => $name, 'callable' => $callable];
    }

    /**
     * @param object $pdo YeAPF PDO connection
     */
    public function runAll(object $pdo, SchemaBootstrapLogger $logger, array $context = []): void
    {
        foreach ($this->entries as $entry) {
            $started = hrtime(true);
            try {
                ($entry['callable'])($pdo, $context);
                $durationMs = (int) ((hrtime(true) - $started) / 1_000_000);
                $logger->append('catalog', 'ok', [
                    'action' => $entry['name'],
                    'duration_ms' => (string) $durationMs,
                ]);
            } catch (\Throwable $e) {
                $durationMs = (int) ((hrtime(true) - $started) / 1_000_000);
                $logger->append('catalog', 'error', [
                    'action' => $entry['name'],
                    'reason' => $e->getMessage(),
                    'duration_ms' => (string) $durationMs,
                ]);
                throw $e;
            }
        }
    }

    public function count(): int
    {
        return count($this->entries);
    }
}
