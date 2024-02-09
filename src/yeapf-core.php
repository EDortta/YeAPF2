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
    $debugAreas = \YeAPF\YeAPFConfig::getSection('mode')->debug->areas ?? [];
    $intDebugAreas = [];
    foreach ($debugAreas as $k) {
      $intDebugAreas[] = DebugLabels::get($k);
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
      $intTraceAreas[] = DebugLabels::get($k);
    }
    \YeAPF\yLogger::defineTraceFilters($traceLevel, $intTraceAreas);
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

if ((!file_exists(__DIR__ . '/misc/yTypes.php')) || (filemtime(__DIR__ . '/yeapf-definitions.php') > filemtime(__DIR__ . '/misc/yTypes.php'))) {
  require_once __DIR__ . '/misc/yGenerateBasicTypes.php';
}

require_once __DIR__ . '/misc/yTypes.php';

\YeAPF\Plugins\PluginList::loadPlugins(__DIR__ . '/plugins');
\YeAPF\Plugins\PluginList::loadPlugins('plugins');

\YeAPF\yLogger::defineLogTag('Application');
