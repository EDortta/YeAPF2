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

$libs = ['yloader.php'];
foreach ($libs as $libName) {
  /**
   * YeAPF2 can reside in core or lib folder
   **/
  if (is_dir("core")) {
    $_libName = "core/$libName";
  } else {
    $_libName = "lib/$libName";
  }

  if (file_exists($_libName)) {
    ((@include_once "$_libName") || die("Error loading $_libName"));
  } else {
    die("$_libName not found");
  }
}

/**
 * necessário para termos CFGApp pronta
 */
_configurarAplicativo();

/**
 * Construo um contexto de operação
 * Poderia usar $GLOBALS que iria dar na mesma
 * mas aqui fica mais claro como limitar as coisas.
 * yAnaliser->do() vai enxergar apenas o que estiver no 'CFGContexto'
 **/
$CFGContexto = array_merge(
  _extractSimilarValues($CFGApp, "layout_"),
  _extractSimilarValues($CFGApp, "html_"),
  [
    'CFGSiteFolder'     => $CFGSiteFolder,
    'CFGSiteURL'        => $CFGSiteURL,
    'CFGSiteAPI'        => $CFGSiteAPI,
    'CFGToken'          => $CFGToken,
    'CFGSiteURLAdm'     => $CFGSiteURLAdm,
    'CFGURL'            => mb_strtolower(getDomain($CFGSiteURL)),

    'agencia_custom_id' => _getValue($CFGApp, 'agencia_custom_id'),
    'nome_agencia'      => _getValue($CFGApp, 'nome_agencia'),
    'email_agencia'     => _getValue($CFGApp, 'email_agencia'),

    'css_files'         => _getValue($CFGApp, 'css_files'),

  ]);

/**
 * Toda toda começa com um substantivo ao que chamamos de subject (assunto)
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
 * Carrego os módulos que estejam disponíveis
 */
$pluginManager->loadPlugins("modules");

/**
 * Carrego os plugins que estejam disponíveis
 */
$pluginManager->loadPlugins("plugins");

$css_files_aux                 = explode(",", $CFGContexto['css_files']);
$CFGContexto['css_files_html'] = '';
foreach ($css_files_aux as $ndx => $cssFile) {
  $cssFile = trim($cssFile);
  $CFGContexto['css_files_html'] .= "\t<link href='.assets/css/$cssFile' rel='stylesheet'>\n";
}

$CFGContexto['html_body'] = $pluginManager->callPlugin($subject, $action);

/**
 * Template a ser utilizado
 * 1) sem rota, utiliza html_template_full
 * 2) login, recuperarSenha, cadastro utilizam html_template_full
 * 3) todos os restante, utilizam html_template_menu
 */

if (in_array($subject, ['welcome', 'login', 'recuperarSenha', 'cadastro', 'logoff'])) {
  $index_name = _getValue($CFGContexto, 'html_template_full', "e_index_full.html");
} else {
  $index_name = _getValue($CFGContexto, 'html_template_menu', "e_index_menu.html");
}

if (!file_exists($index_name)) {
  _die("Arquivo $index_name não localizado");
}
die(__FILE__ . " at " . __LINE__);
$index = file_get_contents($index_name);

/**
 * Processo e mostro o documento
 */
echo $yAnaliser->do($index, $CFGContexto);
echo "<!-- $index_name -->";