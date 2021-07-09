<?php
//https://www.w3schools.com/tags/ref_language_codes.asp
// https://www.baeldung.com/java-8-localization

/**
 * This extensions implement the macro #translate()
 * It can take from 1 to 3 parameters:
 *   The phrase to be translated
 *   The source language and
 *   The target language
 */
class yextensioni18n {
  public function _translate($params) {
    global $CFGContext;

    /**
     * IBM Cloud
     * qbA_UKi0lU2VAHf0cC8dmO99rLtLZUW1vDxp3gEyhlbx
     * ApiKey-63e469e2-91eb-4b8e-8f30-0ba294a9de93
     *
     * YeAPF2
     * kqcRUpUx4cE2J5C4V1jqo7HAEW2MiQj3kImD3dSExrSX
     * https://api.us-south.language-translator.watson.cloud.ibm.com/instances/9f34d7f9-2fc6-44db-b116-28d043b66729
     */

    $translateIBM = function ($text, $model_id) {
      // curl -X POST -u "apikey:6ywirUqvyL-qSiR-Ri3DMfdZTgwn0LVc4BhW9DWV_-YG" --header "Content-Type: application/json" --data "{\"text\": [\"Hello, world! \", \"How are you?\"], \"model_id\":\"en-es\"}" "https://api.us-south.language-translator.watson.cloud.ibm.com/instances/9f34d7f9-2fc6-44db-b116-28d043b66729/v3/translate?version=2018-05-01"

      global $api;

      $ret = _emptyRet();

      list($err, $translations) = httpClient(
        "POST",
        "https://api.us-south.language-translator.watson.cloud.ibm.com/instances/9f34d7f9-2fc6-44db-b116-28d043b66729/v3/translate?version=2018-05-01",
        [
          "text"     => $text,
          "model_id" => $model_id,
        ],
        [
          CURLOPT_USERPWD => 'apikey:6ywirUqvyL-qSiR-Ri3DMfdZTgwn0LVc4BhW9DWV_-YG',
        ]
      );
      if ($err == '') {
        if (!empty($translations['error'])) {
          $ret['http_code']  = 202;
          $ret['error_code'] = $translations['code'];
          $ret['error_msg']  = $translations['error'];
        } else {
          $ret['translation'] = "";
          $aux                = json_decode($translations, true);
          $trans              = $aux['translations'];
          foreach ($trans as $line) {
            if ($ret['translation'] > '') {
              $ret['translation'] .= "\n";
            }
            $ret['translation'] .= json_encode($line['translation']);
          }
        }
      } else {
        $ret['http_code']  = 400;
        $ret['error_code'] = 400;
        $ret['error_msg']  = $err;
      }
      return $ret;
    };

    $ret = _emptyRet();

    $cacheLocation = _grantCacheFolder(".i18n");
    $that          = $params['caller'];

    $defaultSourceLang = _getValue($CFGContext, 'defaultSourceLang', 'en');
    $defaultTargetLang = _getValue($CFGContext, 'defaultTargetLang', 'es');

    $phrase     = $that->getParamValue($params, 0, 0);
    $sourceLang = $that->getParamValue($params, 1, $defaultSourceLang);
    $targetLang = $that->getParamValue($params, 2, $defaultTargetLang);

    $saveFileFlag = false;
    if (preg_match('/^i18n_([a-zA-Z]{1}[a-zA-Z_0-9]{2,}):(.*)/', $phrase, $i18n_tags)) {
      $tag    = $i18n_tags[1];
      $text   = $i18n_tags[2];
      $tagged = true;
    } else {
      $tag    = md5($phrase);
      $text   = $phrase;
      $tagged = false;
    }

    $formalTag      = $targetLang . "-" . $tag . "-" . $sourceLang;
    $formalFilename = "$cacheLocation/$formalTag.text";

    if (file_exists($formalFilename)) {
      $ret['translation'] = @file_get_contents($formalFilename);
    } else {
      if ($text > '') {
        $ret['translation'] = $translateIBM([$text], "$sourceLang-$targetLang");
        $saveFileFlag = true;
      }
    }

    if ($ret['error_code'] == 0) {
      if ($saveFileFlag) {
        @file_put_contents($formalFilename, $ret['translation']);
      }
      return preg_replace('/u([\da-fA-F]{4})/', '&#x\1;', $ret['translation']);
    } else {
      return $ret['error_msg'];
    }
  }
}

$yAnalyzer->adoptClass("yextensioni18n");
