<!--
 * Project: %APP_NAME%
 * Version: %api_VERSION_SEQUENCE%
 * Date: %api_VERSION_DATE%
 * File: %FILE_NAME%
 * Last Modification: %LAST_FILE_MODIFICATION%
 * %COPYRIGHT_NOTE%
 -->

<?php

phpinfo();
die();
  header("Location: ".$_SERVER['REQUEST_SCHEME'] . "://" .$_SERVER['HTTP_HOST'].'/404.html');
?>
