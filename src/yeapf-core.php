<?php declare(strict_types=1);

namespace YeAPF;

require_once __DIR__ . '/yeapf-definitions.php';
require_once __DIR__ . '/yeapf-assets.php';

require_once __DIR__ . '/misc/yLogger.php';
require_once __DIR__ . '/yeapf-debug-definitions.php';
require_once __DIR__ . '/yeapf-debug-labels.php';

require_once __DIR__ . '/yeapf-single-logger.php';
\YeAPF\yLogger::defineLogTag('YeAPF StubLoader');

require_once __DIR__ . '/yeapf-library.php';
require_once __DIR__ . '/yeapf-exception.php';
require_once __DIR__ . '/yeapf-config.php';
(
  function () {
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

    $debugConfig = \YeAPF\YeAPFConfig::getSection('mode')->debug ?? json_decode("{'enabled': false,'level': 'WARNING'}", false);
    $logEnabled = $debugConfig->enabled ?? false;
    $logLevelStr = $debugConfig->level ?? 'WARNING';
    if (!$logEnabled) {
      $logLevel = YeAPF_LOG_EMERG + 100;
    } else {
      if (is_numeric($logLevelStr)) {
        $logLevel = intval($logLevelStr);
      } else {
        $logLevel = $logLevelMap[$logLevelStr] ?? YeAPF_LOG_WARNING;
      }
    }

    $logFacilities = \YeAPF\YeAPFConfig::getSection('mode')->debug->facility ?? [];
    $intLogFacilities = 0;
    foreach ($logFacilities as $k) {
      $intLogFacilities |= ($logFacilityMap[$k]??0);
    }
    \YeAPF\yLogger::setLogFacility($intLogFacilities);


    $debugAreas = \YeAPF\YeAPFConfig::getSection('mode')->debug->areas ?? [];
    $intDebugAreas = [];
    foreach ($debugAreas as $k) {
      $intDebugAreas[] = DebugLabels::get($k)??$k;
    }
    \YeAPF\yLogger::defineLogFilters($logLevel, $intDebugAreas);

    $traceConfig = \YeAPF\YeAPFConfig::getSection('mode')->trace ?? json_decode("{'enabled': false,'level': 'EMERG'}", false);
    $traceEnabled = $traceConfig->enabled ?? false;
    $traceLevelStr = $traceConfig->level ?? 'EMERG';
    if (!$traceEnabled) {
      $traceLevel = YeAPF_LOG_EMERG + 100;
    } else {
      if (is_numeric($traceLevelStr)) {
        $traceLevel = intval($traceLevelStr);
      } else {
        $traceLevel = $logLevelMap[$traceLevelStr] ?? YeAPF_LOG_EMERG;
      }
    }
    $traceAreas = \YeAPF\YeAPFConfig::getSection('mode')->trace->areas ?? [];
    $intTraceAreas = [];
    foreach ($traceAreas as $k) {
      $intTraceAreas[] = DebugLabels::get($k)??$k;
    }
    \YeAPF\yLogger::defineTraceFilters(
      traceLevel: $traceLevel, 
      activeTraceAreas: $intTraceAreas,
      bufferedOutput: true,
      traceToLog: false
    );
  }
)();

\_log('YeAPF Core');

(function () {
  global $definedClasses;

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
    'service/yeapf-http2-service.php',
    'security/yeapf-jwt.php',
    'webapp/yeapf-webapp.php',
  ];
  foreach ($libraryList as $libFilename) {
    \_log("  Loading '$libFilename'");
    require_once __DIR__ . '/' . $libFilename;
    checkClassesRequirements();
  }
  \_log('Core Ready');
})();

\_log('YeAPF Basic Types');


$__yTypesObsolete=(function () {
  global $__yTypesObsolete;
  clearstatcache();  
  if (!file_exists(__DIR__ . '/misc/yTypes.php')) {
    $__yTypesObsolete=true;   
  } else {
    $t1 = filemtime(__DIR__ . '/misc/yTypes.php');
    $t2 = filemtime(__DIR__ . '/yeapf-definitions.php');
    $t3 = filemtime(__DIR__ . '/misc/yGenerateBasicTypes.php');
    $__yTypesObsolete = ($t1 < $t2) || ($t1 < $t3);
  }
  return $__yTypesObsolete;
})();

if ($__yTypesObsolete) {
  require_once __DIR__ . '/misc/yGenerateBasicTypes.php';
}
require_once __DIR__ . '/misc/yTypes.php';

\YeAPF\Plugins\PluginList::loadPlugins(__DIR__ . '/plugins');
\YeAPF\Plugins\PluginList::loadPlugins('plugins');

\YeAPF\yLogger::defineLogTag('Application');
