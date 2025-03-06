<?php declare(strict_types=1);

namespace YeAPF;

require_once __DIR__ . '/yeapf-definitions.php';
require_once __DIR__ . '/yeapf-assets.php';
require_once __DIR__ . '/misc/yLogger.php';
require_once __DIR__ . '/yeapf-debug-definitions.php';
require_once __DIR__ . '/yeapf-debug-labels.php';
require_once __DIR__ . '/yeapf-single-logger.php';
require_once __DIR__ . '/yeapf-library.php';
require_once __DIR__ . '/yeapf-exception.php';
require_once __DIR__ . '/yeapf-config.php';

\YeAPF\yLogger::defineLogTag('YeAPF Core');

/**
 * Initializes logging configurations from YeAPFConfig.
 */
(function () {
    $logLevelMap = [
        'DEBUG' => YeAPF_LOG_DEBUG,
        'INFO' => YeAPF_LOG_INFO,
        'NOTICE' => YeAPF_LOG_NOTICE,
        'WARNING' => YeAPF_LOG_WARNING,
        'ERROR' => YeAPF_LOG_ERROR,
        'CRITICAL' => YeAPF_LOG_CRITICAL,
        'ALERT' => YeAPF_LOG_ALERT,
        'EMERG' => YeAPF_LOG_EMERG,
    ];

    $logFacilityMap = [
        'FILE' => YeAPF_LOG_USING_FILE,
        'DB' => YeAPF_LOG_USING_DB,
        'CONSOLE' => YeAPF_LOG_USING_CONSOLE,
        'SYSLOG' => YeAPF_LOG_USING_SYSLOG
    ];

    $debugConfig = \YeAPF\YeAPFConfig::getSection('mode')->debug ?? (object)['enabled' => false, 'level' => 'WARNING'];
    $logEnabled = $debugConfig->enabled ?? false;
    $logLevelStr = $debugConfig->level ?? 'WARNING';

    $logLevel = $logEnabled ? ($logLevelMap[$logLevelStr] ?? YeAPF_LOG_WARNING) : YeAPF_LOG_EMERG + 100;
    
    $logFacilities = \YeAPF\YeAPFConfig::getSection('mode')->debug->facility ?? [];
    $intLogFacilities = array_reduce($logFacilities, fn($carry, $facility) => $carry | ($logFacilityMap[$facility] ?? 0), 0);
    
    \YeAPF\yLogger::setLogFacility($intLogFacilities);

    $debugAreas = \YeAPF\YeAPFConfig::getSection('mode')->debug->areas ?? [];
    $intDebugAreas = array_map(fn($area) => DebugLabels::get($area) ?? $area, $debugAreas);
    \YeAPF\yLogger::defineLogFilters($logLevel, $intDebugAreas);

    $traceConfig = \YeAPF\YeAPFConfig::getSection('mode')->trace ?? (object)['enabled' => false, 'level' => 'EMERG'];
    $traceEnabled = $traceConfig->enabled ?? false;
    $traceLevelStr = $traceConfig->level ?? 'EMERG';

    $traceLevel = $traceEnabled ? ($logLevelMap[$traceLevelStr] ?? YeAPF_LOG_EMERG) : YeAPF_LOG_EMERG + 100;

    $traceAreas = \YeAPF\YeAPFConfig::getSection('mode')->trace->areas ?? [];
    $intTraceAreas = array_map(fn($area) => DebugLabels::get($area) ?? $area, $traceAreas);

    \YeAPF\yLogger::defineTraceFilters(
        traceLevel: $traceLevel,
        activeTraceAreas: $intTraceAreas,
        bufferedOutput: true,
        traceToLog: false
    );
})();

\_log('YeAPF Core Initialization');

/**
 * Loads required core libraries.
 */
(function () {
    $libraryList = [
        'misc/yAnalyzer.php',
        'misc/yLock.php',
        'misc/yParser.php',
        'misc/yDataFiller.php',
        'vendor/nusoap/nusoap.php',
        'classes/class.key-data.php',
        'classes/class.result.php',
        'bulletin/yeapf-bulletin.php',
        'classes/class.plugins.php',
        'classes/class.plugin-template.php',
        'request/yeapf-request.php',
        'database/yeapf-connection.php',
        'database/yeapf-redis-connection.php',
        'database/yeapf-pdo-connection.php',
        'database/yeapf-persistence-interface.php',
        'database/yeapf-collections.php',
        'database/yeapf-eyeshot.php',
        'classes/class.route-definition.php',
        'classes/class.service-base.php',
        'service/yeapf-service-skeleton.php',
        'service/yeapf-http2-service.php',
        'service/yeapf-sse-service.php',
        'security/yeapf-jwt.php',
        'webapp/yeapf-webapp.php',
    ];

    foreach ($libraryList as $libFilename) {
        \_log("Loading '$libFilename'");
        require_once __DIR__ . '/' . $libFilename;
        checkClassesRequirements();
    }

    \_log('Core Ready');
})();

\_log('YeAPF Basic Types');

$__yTypesObsolete = (function () {
    clearstatcache();  
    if (!file_exists(__DIR__ . '/misc/yTypes.php')) {
        return true;
    }
    
    $t1 = filemtime(__DIR__ . '/misc/yTypes.php');
    $t2 = filemtime(__DIR__ . '/yeapf-definitions.php');
    $t3 = filemtime(__DIR__ . '/misc/yGenerateBasicTypes.php');
    
    return ($t1 < $t2) || ($t1 < $t3);
})();

if ($__yTypesObsolete) {
    require_once __DIR__ . '/misc/yGenerateBasicTypes.php';
}

require_once __DIR__ . '/misc/yTypes.php';
require_once __DIR__ . '/misc/yTypeChecker.php';

\YeAPF\Plugins\PluginList::loadPlugins(__DIR__ . '/plugins');
\YeAPF\Plugins\PluginList::loadPlugins('plugins');

\YeAPF\yLogger::defineLogTag('Application');
