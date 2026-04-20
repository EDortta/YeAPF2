<?php declare(strict_types=1);

namespace YeAPF\Connection\DB\Driver;

interface DBDriverInterface
{
    public function getDriverName(): string;

    public function getDriverVersion(): ?string;

    public function getCapabilities(): DriverCapabilities;

    public function execute(string $sql, array $params = []): int;

    public function fetchOne(string $sql, array $params = []): ?array;

    public function fetchAll(string $sql, array $params = []): array;

    public function beginTransaction(): void;

    public function commit(): void;

    public function rollBack(): void;

    /**
     * @param array<string,mixed> $params
     * @return array{
     *   driver: string,
     *   sql_state: string|null,
     *   driver_code: int|string|null,
     *   message: string,
     *   normalized_code: string,
     *   is_transient: bool,
     *   context: array<string,mixed>
     * }
     */
    public function normalizeError(\Throwable $throwable, ?string $sql = null, array $params = []): array;
}
