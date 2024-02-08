<?php
declare (strict_types = 1);
namespace YeAPF;

require_once __DIR__ . "/yeapf-definitions.php";
require_once __DIR__ . "/yeapf-assets.php";

require_once __DIR__ . "/misc/yLogger.php";
require_once __DIR__ . "/yeapf-single-logger.php";
\YeAPF\yLogger::defineLogTagAndLevel("YeAPF StubLoader", LOG_NOTICE);

require_once __DIR__ . "/yeapf-library.php";
require_once __DIR__ . "/yeapf-exception.php";
require_once __DIR__ . "/yeapf-config.php";

\_log("YeAPF Core");

(function () {
  global $definedClasses;
  $libraryList = [
    "misc/yAnalyzer.php",
    "misc/yLock.php",
    "misc/yParser.php",
    "misc/yDataFiller.php",

    "vendor/nusoap/nusoap.php",

    "classes/class.key-data.php",
    "classes/class.result.php",
    "bulletin/yeapf-bulletin.php",

    "classes/class.plugins.php",
    "classes/class.plugin-template.php",

    "request/yeapf-request.php",

    "database/yeapf-connection.php",
    "database/yeapf-redis-connection.php",
    "database/yeapf-pdo-connection.php",
    "database/yeapf-persistence-interface.php",

    "database/yeapf-collections.php",
    "database/yeapf-eyeshot.php",

    "service/yeapf-http2-service.php",
    "security/yeapf-jwt.php",

    "webapp/yeapf-webapp.php",
  ];
  foreach ($libraryList as $libFilename) {
    \_log("  Loading '$libFilename'");
    require_once __DIR__ . "/" . $libFilename;
    checkClassesRequirements();
  }
  \_log("Core Ready");
})();


\_log("YeAPF Basic Types");

if ((!file_exists(__DIR__ . "/misc/yTypes.php")) || (filemtime(__DIR__ . "/yeapf-definitions.php") > filemtime(__DIR__ . "/misc/yTypes.php"))) {
    require_once __DIR__ . "/misc/yGenerateBasicTypes.php";
}

require_once __DIR__ . "/misc/yTypes.php";


\YeAPF\Plugins\PluginList::loadPlugins(__DIR__ . "/plugins");
\YeAPF\Plugins\PluginList::loadPlugins("plugins");

\YeAPF\yLogger::defineLogTagAndLevel("Application", LOG_NOTICE);