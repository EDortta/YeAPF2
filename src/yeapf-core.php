<?php
declare (strict_types = 1);
namespace YeAPF;

require_once __DIR__ . "/yeapf-assets.php";

require_once __DIR__ . "/misc/yLogger.php";
require_once __DIR__ . "/yeapf-single-logger.php";

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
  ];
  foreach ($libraryList as $libFilename) {
    \_log("  Loading '$libFilename'");
    require_once __DIR__ . "/" . $libFilename;
    checkClassesRequirements();
  }
  \_log("Core Ready");
})();

\YeAPF\Plugins\PluginList::loadPlugins(__DIR__ . "/plugins");
\YeAPF\Plugins\PluginList::loadPlugins("plugins");