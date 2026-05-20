<?php declare(strict_types=1);

namespace YeAPF\SchemaBootstrap;

final class SchemaAlignmentService
{
    public function __construct(
        private readonly SchemaBootstrapLogger $logger
    ) {
    }

    /**
     * Align all document models using PersistentCollection grantCollection + FK/index DDL.
     *
     * @param \YeAPF\Connection\PersistenceContext $context
     */
    public function alignAll(
        object $context,
        string $documentModelsDir,
        object $pdo
    ): void {
        $manifests = DocumentModelManifest::loadAll($documentModelsDir);

        foreach ($manifests as $manifest) {
            $this->alignCollection($context, $manifest, $pdo);
        }
    }

    /**
     * @param \YeAPF\Connection\PersistenceContext $context
     * @param array<string,mixed> $manifest
     */
    private function alignCollection(object $context, array $manifest, object $pdo): void
    {
        $table = (string) $manifest['table'];
        $started = hrtime(true);

        $model = new \YeAPF\ORM\DocumentModel($context, $table);
        foreach ($manifest['constraints'] as $field => $constraint) {
            if (!is_array($constraint)) {
                continue;
            }
            $type = $constraint['type'] ?? YeAPF_TYPE_STRING;
            $model->setConstraint(
                $field,
                $type,
                length: $constraint['length'] ?? null,
                primary: (bool) ($constraint['primary'] ?? false),
                required: (bool) ($constraint['required'] ?? false),
                acceptNULL: (bool) ($constraint['acceptNULL'] ?? true),
                unique: (bool) ($constraint['unique'] ?? false),
                protobufOrder: (int) ($constraint['protobufOrder'] ?? 0)
            );
        }

        $collection = new \YeAPF\ORM\PersistentCollection(
            $context,
            $table,
            $this->resolveIdField($manifest['constraints']),
            $model
        );

        $durationMs = (int) ((hrtime(true) - $started) / 1_000_000);
        $this->logger->append('align', 'ok', [
            'collection' => $table,
            'action' => 'grant_collection',
            'duration_ms' => (string) $durationMs,
        ]);

        foreach ($manifest['foreignKeys'] as $fk) {
            if (!is_array($fk)) {
                continue;
            }
            $this->applyForeignKey($pdo, $table, $fk);
        }

        foreach ($manifest['indexes'] as $index) {
            if (!is_array($index)) {
                continue;
            }
            $this->applyIndex($pdo, $table, $index);
        }
    }

    /**
     * @param array<string,array<string,mixed>> $constraints
     */
    private function resolveIdField(array $constraints): string
    {
        foreach ($constraints as $name => $constraint) {
            if (!empty($constraint['primary'])) {
                return (string) $name;
            }
        }

        return 'id';
    }

    /**
     * @param array<string,mixed> $fk
     */
    private function applyForeignKey(object $pdo, string $table, array $fk): void
    {
        $columns = $fk['columns'] ?? [];
        $references = $fk['references'] ?? '';
        if (!is_array($columns) || $columns === [] || $references === '') {
            return;
        }

        $constraintName = $fk['name'] ?? ('fk_' . $table . '_' . implode('_', $columns));
        if ($this->foreignKeyExists($pdo, $table, (string) $constraintName)) {
            return;
        }

        $onDelete = strtoupper((string) ($fk['onDelete'] ?? 'NO ACTION'));
        $cols = implode(', ', array_map(static fn ($c) => '"' . $c . '"', $columns));
        $sql = sprintf(
            'ALTER TABLE "%s" ADD CONSTRAINT "%s" FOREIGN KEY (%s) REFERENCES "%s"(id) ON DELETE %s',
            $table,
            $constraintName,
            $cols,
            $references,
            $onDelete
        );

        $pdo->query($sql);
        $this->logger->append('align', 'ok', [
            'collection' => $table,
            'action' => 'add_foreign_key',
            'column' => implode(',', $columns),
        ]);
    }

    /**
     * @param array<string,mixed> $index
     */
    private function applyIndex(object $pdo, string $table, array $index): void
    {
        $name = (string) ($index['name'] ?? '');
        $columns = $index['columns'] ?? [];
        if ($name === '' || !is_array($columns) || $columns === []) {
            return;
        }

        if ($this->indexExists($pdo, $table, $name)) {
            return;
        }

        $unique = !empty($index['unique']) ? 'UNIQUE ' : '';
        $cols = implode(', ', array_map(static fn ($c) => '"' . $c . '"', $columns));
        $where = isset($index['where']) ? ' WHERE ' . $index['where'] : '';
        $sql = sprintf(
            'CREATE %sINDEX "%s" ON "%s" (%s)%s',
            $unique,
            $name,
            $table,
            $cols,
            $where
        );

        $pdo->query($sql);
        $this->logger->append('align', 'ok', [
            'collection' => $table,
            'action' => 'add_index',
            'column' => $name,
        ]);
    }

    private function foreignKeyExists(object $pdo, string $table, string $constraintName): bool
    {
        $sql = 'SELECT 1 FROM information_schema.table_constraints
                WHERE constraint_name = ' . $this->sqlLiteral($constraintName) . '
                AND table_name = ' . $this->sqlLiteral($table) . '
                LIMIT 1';
        $result = $pdo->query($sql);
        if ($result === false) {
            return false;
        }

        return (bool) $result->fetch();
    }

    private function indexExists(object $pdo, string $table, string $indexName): bool
    {
        $sql = 'SELECT 1 FROM pg_indexes
                WHERE indexname = ' . $this->sqlLiteral($indexName) . '
                AND tablename = ' . $this->sqlLiteral($table) . '
                LIMIT 1';
        $result = $pdo->query($sql);
        if ($result === false) {
            return false;
        }

        return (bool) $result->fetch();
    }

    private function sqlLiteral(string $value): string
    {
        return "'" . str_replace("'", "''", $value) . "'";
    }
}
