#!/usr/bin/env php
<?php declare(strict_types=1);

/**
 * YeAPF2 schema session bootstrap CLI.
 *
 * Usage:
 *   php bin/y2-schema-bootstrap.php --app-root=/path/to/application [--force]
 */

$root = dirname(__DIR__);
require_once $root . '/src/yeapf-core.php';

$options = getopt('', ['app-root:', 'force']);
$appRoot = $options['app-root'] ?? '';
$force = array_key_exists('force', $options);

if ($appRoot === '' || !is_dir($appRoot)) {
    fwrite(STDERR, "Usage: php bin/y2-schema-bootstrap.php --app-root=/path/to/app [--force]\n");
    exit(1);
}

$appRoot = realpath($appRoot) ?: $appRoot;

$entryScript = is_file($appRoot . '/public/index.php')
    ? $appRoot . '/public/index.php'
    : $appRoot . '/index.php';
$_SERVER['SCRIPT_FILENAME'] = $entryScript;

\YeAPF\YeAPFConfig::open();

$redis = null;
try {
    $redis = new \YeAPF\Connection\DB\RedisConnection();
} catch (\Throwable) {
    // Redis optional for CLI when only FS lock is used.
}

$context = new \YeAPF\Connection\PersistenceContext($redis, null);
$orchestrator = \YeAPF\SchemaBootstrap\SchemaSessionOrchestrator::factory($appRoot, $context, $redis);

$main = \YeAPF\Connection\DB\CreateMainPDOConnection();
$conn = null;
$main->popConnection($conn);

try {
    $hash = $orchestrator->run($context, $conn, $force);
    fwrite(STDOUT, "Schema bootstrap completed. schemaHash={$hash}\n");
    exit(0);
} catch (\YeAPF\SchemaBootstrap\SchemaBootstrappedException $e) {
    fwrite(STDERR, 'Bootstrap blocked: ' . $e->getMessage() . "\n");
    exit(2);
} catch (\Throwable $e) {
    fwrite(STDERR, 'Bootstrap failed: ' . $e->getMessage() . "\n");
    exit(1);
} finally {
    $main->pushConnection($conn);
}
