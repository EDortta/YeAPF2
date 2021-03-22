<?php
/**
 * Project: %APP_NAME%
 * Version: %api_VERSION_SEQUENCE%
 * Date: %api_VERSION_DATE%
 * File: %FILE_NAME%
 * Last Modification: %LAST_FILE_MODIFICATION%
 * %COPYRIGHT_NOTE%
 *
 **/

date_default_timezone_set('America/Sao_Paulo');

if (!defined("API_INTERNAL")) {
/**
 * These constants are used to group entry points when generating automated documentation.
 * As a rule, we use base 2 powers so that we can mix delivery to the customer. The first 8 are
 * reserved for basic use of api.php. That is, the values 1, 2, 4, 8, 16, 32, 64 and 128 should
 * not be used by the final programmer as it is at risk of being overlapped by other definitions
 * that we may implement in the future.
 **/
  define("API_INTERNAL", 1);
  define("API_BACKYARD", 2);
  define("API_WEB", 4);
  define("API_MOBILE", 8);

  define("API_DEFAULT", API_INTERNAL | API_WEB | API_MOBILE);

  global $__api_applicability_names;
  $__api_applicability_names = [
    API_INTERNAL => "Internal",
    API_BACKYARD => "Backyard",
    API_WEB      => "Web",
    API_MOBILE   => "Mobile",
  ];

}
