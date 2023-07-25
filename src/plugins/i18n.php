<?php
declare (strict_types = 1);
namespace YeAPF\i18n;

use OpenSwoole;
use OpenSwoole\Coroutine;
use OpenSwoole\Coroutine\WaitGroup;
use OpenSwoole\Coroutine\Channel;

class Translator extends \YeAPF\Plugins\ServicePlugin implements \YeAPF\Plugins\ServicePluginInterface
{
  use \YeAPF\Assets;
  private static $config;
  private static $assetsFolder;
  private static $context;
  private static $persistentTranslations;

  private static function grantStructure() {
    $i18nDataModel = new \YeAPF\ORM\DOcumentModel(self::$context, "translations");
    $i18nDataModel->setConstraint(
      keyName:"id",
      keyType:YeAPF_TYPE_STRING,
      length:36,
      primary:true,
      protobufOrder:0
    );

    $i18nDataModel->setConstraint(
      keyName:"tag",
      keyType:YeAPF_TYPE_STRING,
      length:64,
      protobufOrder:1
    );

    $i18nDataModel->setConstraint(
      keyName:"lang",
      keyType:YeAPF_TYPE_STRING,
      protobufOrder:2,
      length:5
    );

    $i18nDataModel->setConstraint(
      keyName:"text",
      keyType:YeAPF_TYPE_STRING,
      protobufOrder:0
    );

    self::$persistentTranslations = new \YeAPF\ORM\PersistentCollection(
      self::$context,
      "translations",
      "id",
      $i18nDataModel
    );

  }

  static function getAssetsFolder(): string {
    return \YeAPF\YeAPFConfig::getGLobalAssetsFolder()."/i18n/";
  }

  static function canWorkWithoutAssets(): bool {
    return false;
  }

  function __construct() {
    _log("Building i18n plugin\n");
    parent::__construct(__FILE__);
    if (!is_dir(self::getAssetsFolder())) {
      mkdir(self::getAssetsFolder(), 0777, true);
    }
    if (!is_writable(self::getAssetsFolder())) {
      throw new \Exception("Assets folder ". self::getAssetsFolder() .  " is not writable");
    }
    self::$config = \YeAPF\YeAPFConfig::getSection("i18n");
    self::$context = new \YeAPF\Connection\PersistenceContext(
        new \YeAPF\Connection\DB\RedisConnection(),
        new \YeAPF\Connection\DB\PDOConnection()
    );
    $this->grantStructure();
  }

  private function saveIntoAssets(\YeAPF\SanitizedKeyData $data) {
    $filename = self::getAssetsFolder() . "/". $data->tag . "/". $data->lang . ".txt";
    mkdir(dirname($filename), 0777, true);
    file_put_contents($filename, $data->text);
  }

  private function loadFromAssets(\YeAPF\SanitizedKeyData $data) {
    $filename = self::getAssetsFolder() . "/". $data->tag . "/". $data->lang . ".txt";
    if (file_exists($filename))
      $data->text = file_get_contents($filename);
  }

  public function translate(
    string $scope = null,
    string $targetLang = null,
    string $tag = null,
    string $DOMText = null
  ) {
    $ret = [];

    if (strpos($targetLang, '-') !== false) {
      $targetLang = substr($targetLang, 0, strpos($targetLang, '-'));
    }

    $tags = explode(':', $tag);

    // $waitGroup = new WaitGroup();
    $channel = new Channel(count($tags));


    foreach ($tags as $tag) {
      // $waitGroup->add();
      Coroutine::create(function () use ($tag, $scope, $targetLang, $DOMText, $channel, &$ret) {
        $channel->push(true);
        try {
          $result = [];
          $tag = trim($tag);
          if (0<strlen($tag)) {
            /**
             * Check it the text is already in database
             */
            $translatedText       = clone self::$persistentTranslations->getDocumentModel();
            $translatedText->tag  = $tag;
            $translatedText->lang = $targetLang;

            _log("Translated: " . json_encode($translatedText) . "\n");

            $alreadyTranslated = self::$persistentTranslations->findByExample($translatedText);

            _log("Already translated: " . json_encode($alreadyTranslated) . "\n");

            if ($alreadyTranslated && !empty($alreadyTranslated['text'])) {
              $result['text'] = $alreadyTranslated['text'];
              $result['cached']  = true;
            } else {
              $translationRequired = ($targetLang != 'en');

              /**
               * Get the original text from database
               */
              $originalText       = clone self::$persistentTranslations->getDocumentModel();
              $originalText->tag  = $tag;
              $originalText->lang = 'en';

              $original = self::$persistentTranslations->findByExample($originalText);
              _log("Original: " . json_encode($original) . "\n");
              _log("  id: " . $original['id'] . "\n");

              if (false == $original || empty($original['text'])) {
                if (null != $DOMText && 0<strlen($DOMText)) {

                }
                if (null != $DOMText && 0<strlen($DOMText)) {
                  /**
                   * TODO
                   *   Check the operational mode is development or at least "not-production"
                   */
                  _log("DOMText: $DOMText\n");
                  $originalText->text = trim(html_entity_decode($DOMText, ENT_QUOTES, "UTF-8"));
                  _log("Text to be saved: ".$originalText->text);
                  $originalText->id   = \YeAPF\generateUniqueId();
                  _log("Saving original version");
                  self::$persistentTranslations->setDocument($originalText->id, $originalText);
                  _log("Saved text: ".$originalText->text);
                  $original = clone $originalText;
                } else {
                  $translationRequired = false;
                }
              }

              if ($translationRequired) {
                if ("watson" == self::$config->model) {
                  $wordCount = str_word_count($original->text??'');

                  _log("Translation required\n");
                  _log("Current 'original' state " . json_encode($original) . "\n");
                  _log("'original' id: '" . $original->id . "'\n");
                  _log("'original' text: '" . $original->text . "'\n");
                  _log("Words to be translated: " . $wordCount . "\n");
                  $url    = self::$config->url;
                  $apikey = self::$config->apikey;

                  if (0 < $wordCount) {

                    $auxText = trim(html_entity_decode($original->text, ENT_QUOTES, "UTF-8"));

                    _log("Text to be translated: $auxText\n");
                    self::loadFromAssets($translatedText);
                    if (is_null($translatedText->text) || 0==strlen($translatedText->text)) {
                      $watson = \YeAPF\Request::do($url, "POST", [
                        "text"     => [$auxText],
                        "model_id" => 'en-' . $targetLang,
                      ], [
                        "username" => "apikey",
                        "password" => $apikey,
                      ]);

                      _log(var_dump($watson));

                      $translatedPhrase = "";
                      $aux              = json_decode($watson, true);
                      $trans            = $aux['translations'];
                      foreach ($trans as $line) {
                        if ($translatedPhrase > '') {
                          $translatedPhrase .= "\n";
                        }
                        $translatedPhrase .= $line['translation'];
                      }

                      $result['translated'] = true;
                      $translatedText->text   = $translatedPhrase;
                      $result['cached']  = false;
                    } else {
                      \_log("Using translation saved on assets: ". $translatedText->text);
                      $translatedPhrase = $translatedText->text;
                      $result['cached']  = true;
                    }

                    $translatedText->id     = \YeAPF\generateUniqueId();
                    self::$persistentTranslations->setDocument($translatedText->id, $translatedText);
                    $result['text'] = $translatedPhrase;

                    $originalText->text=$auxText;
                    self::saveIntoAssets($originalText);
                    self::saveIntoAssets($translatedText);

                  } else {
                    _log("No text to be translated\n");
                    $result['text'] = "No text to be translated";
                  }
                }
              }

            }


            $ret[$tag] = $result;
          }


        } finally {
          // $waitGroup->done();
          $channel->pop();
        }

      });
    }

    // $waitGroup->wait();
    while (!$channel->isEmpty()) {
      Coroutine::yield();
    }



    return $ret;
  }

  public function registerServiceMethods($server) {

  }
}