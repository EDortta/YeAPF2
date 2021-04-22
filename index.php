<?php
/**
 * Project: %APP_NAME%
 * Version: %core_VERSION_SEQUENCE%
 * Date: %core_VERSION_DATE%
 * File: %FILE_NAME%
 * Last Modification: %LAST_FILE_MODIFICATION%
 * %COPYRIGHT_NOTE%
 **/

// ini_set('display_errors', 1);

$folders     = ['ycore', 'core', 'lib'];
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

/**
 * necessário para termos CFGApp pronta
 */
_configureApp();

/**
 * Construo um contexto de operação
 * Poderia usar $GLOBALS que iria dar na mesma
 * mas aqui fica mais claro como limitar as coisas.
 * yAnaliser->do() vai enxergar apenas o que estiver no 'CFGContext'
 **/
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

    'css_files'     => _getValue($CFGApp, 'css_files'),

  ]);

/**
 * Toda chamada começa com um substantivo ao que chamamos de subject (assunto)
 * podendo estar seguido de um verbo ao que chamamos de action (ação)
 * http://exemplo.com/conta/listarMovimentos
 * que é o mesmo que
 * http://exemplo.com?s=conta&a=listarMovimentos
 */
$subject = _getValue($_GET, 's', '');
$action  = _getValue($_GET, 'a', '');

if (strpos($subject, '/') > 0) {
  $action  = mb_substr($subject, strpos($subject, '/') + 1);
  $subject = mb_substr($subject, 0, strpos($subject, '/'));
}

if ($subject == '') {
  $subject = 'welcome';
}


/**
 * Carrego os plugins que estejam disponíveis
 */

$css_files_aux                = explode(",", $CFGContext['css_files']);
$CFGContext['css_files_html'] = '';
foreach ($css_files_aux as $ndx => $cssFile) {
  $cssFile = trim($cssFile);
  $CFGContext['css_files_html'] .= "\t<link href='.assets/css/$cssFile' rel='stylesheet'>\n";
}

$CFGContext['html_body'] = $pluginManager->callPlugin($subject, $action);

/**
 * Template a ser utilizado
 * 1) sem rota, utiliza html_template_full
 * 2) login, recoverPassword, sign, logout utilizam html_template_full
 * 3) todos os restante, utilizam html_template_menu
 */

if (in_array($subject, ['welcome', 'login', 'recoverPassword', 'sign', 'logout'])) {
  $index_name = _getValue($CFGContext, 'html_template_full', "e_index_full.html");
} else {
  $index_name = _getValue($CFGContext, 'html_template_menu', "e_index_menu.html");
}

if (!file_exists($index_name)) {
  _die("Arquivo $index_name não localizado");
}
//   die(__FILE__ . " at " . __LINE__);
$index = file_get_contents($index_name);

/**
 * Processo e mostro o documento
 */
echo $yAnaliser->do($index, $CFGContext);
echo "<!-- $index_name -->";
