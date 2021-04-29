<?php

/**
 * Project: %APP_NAME%
 * Version: %api_VERSION_SEQUENCE%
 * Date: %api_VERSION_DATE%
 * File: %FILE_NAME%
 * Last Modification: %LAST_FILE_MODIFICATION%
 * %COPYRIGHT_NOTE%
 **/

/**
 * API can be running as a top folder or a subsidiary script
 * As a top folder, the yloader.php can be placed at 'lib' or 'core'
 * folders that belongs to the top folder.
 * But, as subfolder of the main application, yloader can be not
 * only in subsidiary folders as on one-level-up
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Max-Age: 1200");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

$method = $_SERVER['REQUEST_METHOD'];
if ($method == "OPTIONS") {

  header('Access-Control-Allow-Origin: *');
  header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method,Access-Control-Request-Headers, Authorization");
  header("HTTP/1.1 200 OK");

} else {
  $folders     = ['ycore', 'core', 'lib', '../core', '../lib'];
  $yLoaderName = '';
  for ($i = 0; $i < count($folders) && $yLoaderName == ''; $i++) {
    if (file_exists($folders[$i] . "/yloader.php")) {
      $yLoaderName = $folders[$i] . "/yloader.php";
    }
  }

  if (file_exists($yLoaderName)) {
    ((@include_once "$yLoaderName") || die('{ "error": "yloader.php cannot be loaded", "filename": "' . $yLoaderName . '" }'));
  } else {
    die('{ "error": "yloader.php not found" }');
  }

  _configureApp();

  /* you can use $CFGContext as it comes from _configureAPP() or burn it out */
  $CFGContext = array_merge(
    _extractSimilarValues($CFGApp, "layout_"),
    _extractSimilarValues($CFGApp, "html_"),
    [
      'CFGSiteFolder' => $CFGSiteFolder,
      'CFGSiteURL'    => $CFGSiteURL,
      'CFGSiteAPI'    => $CFGSiteAPI,
      'CFGToken'      => $CFGToken,
      'CFGSiteURLAdm' => $CFGSiteURLAdm,
      'CFGURL'        => mb_strtolower(getDomain($CFGSiteURL)),

    ]);

  _log("Producing headers");

  $headers = array();
  foreach ($_SERVER as $key => $value) {
    if (strpos($key, 'HTTP_') === 0) {
      $headers[str_replace(' ', '', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))))] = $value;
    }
  }
  _log("Header request ----- ");
  _log(json_encode($headers, JSON_PRETTY_PRINT));

  header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
  header("Cache-Control: post-check=0, pre-check=0", false);
  header("Pragma: no-cache");

  _log("Executing API call");

  // die(print_r($GLOBALS['__yPluginsRepo']));

  $helpMode = true;
  $helpMap  = false;
  $ret      = $api->execute();

  if ($ret !== false) {
    _log("Returning = " . json_encode($ret));
    _response($ret);
  }
}
