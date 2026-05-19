<?php declare(strict_types=1);

namespace YeAPF\SchemaBootstrap;

final class SchemaBootstrapLogger
{
    public function __construct(
        private readonly SchemaBootstrapPaths $paths
    ) {
    }

    public function append(
        string $phase,
        string $status,
        array $fields = []
    ): void {
        $this->paths->ensureBaseDir();
        $parts = [
            gmdate('Y-m-d\TH:i:s\Z'),
            'pid=' . getmypid(),
            'phase=' . $phase,
            'status=' . $status,
        ];

        foreach ($fields as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $parts[] = $key . '=' . str_replace(["\t", "\n", "\r"], ' ', (string) $value);
        }

        file_put_contents(
            $this->paths->logFile(),
            implode("\t", $parts) . "\n",
            FILE_APPEND | LOCK_EX
        );
    }
}
