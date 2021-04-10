<?php
//https://www.w3schools.com/tags/ref_language_codes.asp
// https://www.baeldung.com/java-8-localization

class yi18n {
  public function _translate($params) {
    global $CFGContext;

    $that = $params['caller'];

    $defaultSourceLang = _getValue($CFGContext, 'defaultSourceLang', 'en');
    $defaultTargetLang = _getValue($CFGContext, 'defaultSourceLang', 'es');
    $phrase            = $that->getParamValue($params, 0, 0);
    $sourceLang        = $that->getParamValue($params, 1, $defaultSourceLang);
    $targetLang        = $that->getParamValue($params, 2, $defaultTargetLang);
    $cacheFolder       = _grantCacheFolder();

    return "Translating $phrase to $targetLang";
  }
}

$yAnalyzer->adoptClass("yi18n");
