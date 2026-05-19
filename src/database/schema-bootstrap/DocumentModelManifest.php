<?php declare(strict_types=1);

namespace YeAPF\SchemaBootstrap;

final class DocumentModelManifest
{
    /**
     * @return list<array{
     *   table:string,
     *   file:string,
     *   constraints:array<string,array<string,mixed>>,
     *   foreignKeys:list<array<string,mixed>>,
     *   indexes:list<array<string,mixed>>
     * }>
     */
    public static function loadAll(string $documentModelsDir): array
    {
        if (!is_dir($documentModelsDir)) {
            return [];
        }

        $files = glob(rtrim($documentModelsDir, '/') . '/*.json') ?: [];
        sort($files);

        $manifests = [];
        foreach ($files as $file) {
            $raw = file_get_contents($file);
            if ($raw === false) {
                continue;
            }

            $decoded = json_decode($raw, true);
            if (!is_array($decoded)) {
                throw new SchemaBootstrappedException('Invalid document model JSON: ' . basename($file));
            }

            $table = basename($file, '.json');
            $constraints = [];
            $foreignKeys = [];
            $indexes = [];

            foreach ($decoded as $key => $value) {
                if ($key === 'foreignKeys' && is_array($value)) {
                    $foreignKeys = $value;
                    continue;
                }
                if ($key === 'indexes' && is_array($value)) {
                    $indexes = $value;
                    continue;
                }
                if (is_array($value)) {
                    $constraints[$key] = $value;
                }
            }

            $manifests[] = [
                'table' => $table,
                'file' => $file,
                'constraints' => $constraints,
                'foreignKeys' => $foreignKeys,
                'indexes' => $indexes,
            ];
        }

        return $manifests;
    }

    public static function hashDirectory(string $documentModelsDir): string
    {
        $manifests = self::loadAll($documentModelsDir);
        $payload = json_encode($manifests, JSON_THROW_ON_ERROR);

        return hash('sha256', $payload);
    }
}
