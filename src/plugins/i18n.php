<?php declare(strict_types=1);

namespace YeAPF\i18n;

use OpenSwoole\Coroutine\Channel;
use OpenSwoole\Coroutine\WaitGroup;
use OpenSwoole\Coroutine;
use OpenSwoole;

class Translator extends \YeAPF\Plugins\ServicePlugin implements \YeAPF\Plugins\ServicePluginInterface
{
  use \YeAPF\Assets;

  private static $config;
  private static $assetsFolder;
  private static $context;
  private static $persistentTranslations;

  private static function grantStructure()
  {
    $i18nDataModel = new \YeAPF\ORM\DOcumentModel(self::$context, 'translations');
    $i18nDataModel->setConstraint(
      keyName: 'id',
      keyType: YeAPF_TYPE_STRING,
      length: 36,
      primary: true,
      protobufOrder: 1
    );

    $i18nDataModel->setConstraint(
      keyName: 'tag',
      keyType: YeAPF_TYPE_STRING,
      length: 64,
      protobufOrder: 2
    );

    $i18nDataModel->setConstraint(
      keyName: 'lang',
      keyType: YeAPF_TYPE_STRING,
      protobufOrder: 3,
      length: 5
    );

    $i18nDataModel->setConstraint(
      keyName: 'text',
      keyType: YeAPF_TYPE_STRING,
      protobufOrder: 4
    );

    self::$persistentTranslations = new \YeAPF\ORM\PersistentCollection(
      self::$context,
      'translations',
      'id',
      $i18nDataModel
    );
  }

  static function getAssetsFolder(): string
  {
    return \YeAPF\YeAPFConfig::getGLobalAssetsFolder() . '/i18n/';
  }

  static function canWorkWithoutAssets(): bool
  {
    return false;
  }

  function __construct()
  {
    _log("Building i18n plugin\n");
    parent::__construct(__FILE__);
    if (!is_dir(self::getAssetsFolder())) {
      mkdir(self::getAssetsFolder(), 0777, true);
    }
    if (!is_writable(self::getAssetsFolder())) {
      throw new \YeAPF\YeAPFException('Assets folder ' . self::getAssetsFolder() . ' is not writable', YeAPF_ASSETS_FOLDER_NOT_WRITABLE);
    }
    self::$config = \YeAPF\YeAPFConfig::getSection('i18n');
    self::$context = new \YeAPF\Connection\PersistenceContext(
      new \YeAPF\Connection\DB\RedisConnection(),
      new \YeAPF\Connection\DB\PDOConnection()
    );
    _log('Granting i18n plugin structure');
    $this->grantStructure();
  }

  private function saveIntoAssets(\YeAPF\SanitizedKeyData $data)
  {
    $filename = self::getAssetsFolder() . '/' . $data->tag . '/' . $data->lang . '.txt';
    if (!is_dir(dirname($filename)))
      mkdir(dirname($filename), 0777, true);
    file_put_contents($filename, $data->text);
  }

  private function loadFromAssets(\YeAPF\SanitizedKeyData $data)
  {
    $filename = self::getAssetsFolder() . '/' . $data->tag . '/' . $data->lang . '.txt';
    if (file_exists($filename))
      $data->text = file_get_contents($filename);
  }

  public function translate(
    string $scope = null,
    string $targetLang = null,
    string $tag = null,
    string $DOMText = null
  ) {
    $debug = false;
    $ret = [];

    if (strpos($targetLang, '-') !== false) {
      $targetLang = substr($targetLang, 0, strpos($targetLang, '-'));
    }

    $tags = explode(':', $tag ?? '');

    if ($debug)
      _log('Tags: ' . json_encode($tags) . "\n");

    // $waitGroup = new WaitGroup();
    $channel = new Channel(count($tags));

    foreach ($tags as $tag) {
      if ($debug)
        _log("  tag = $tag");
      // $waitGroup->add();
      Coroutine::create(function () use ($debug, $tag, $scope, $targetLang, $DOMText, $channel, &$ret) {
        $channel->push(true);
        try {
          $result = [];
          $tag = trim($tag);
          if (0 < strlen($tag)) {
            if ($debug)
              _log('Into coroutine');

            /** Check it the text is already in database */
            try {
              $translatedText = clone self::$persistentTranslations->getDocumentModel();

              if ($debug)
                _log('After clone: ' . print_r($translatedText, true));
              if ($debug)
                _log('Classname: ' . get_class($translatedText));

              $translatedText->tag = $tag;
              if ($debug)
                _log('After clone and tagged: ' . print_r($translatedText, true));
              $translatedText->lang = $targetLang;

              if ($debug)
                _log('Translated: ' . print_r($translatedText, true));

              $alreadyTranslated = self::$persistentTranslations->findByExample($translatedText);

              if ($debug)
                _log('Already translated: ' . print_r($alreadyTranslated, true));

              if ($alreadyTranslated && !empty($alreadyTranslated['text'])) {
                if ($debug)
                  _log('Checkpoint A');
                $result['text'] = $alreadyTranslated['text'];
                $result['cached'] = true;
              } else {
                if ($debug)
                  _log('Checkpoint B');
                $translationRequired = ($targetLang != 'en');

                /** Get the original text from database */
                $originalText = clone self::$persistentTranslations->getDocumentModel();
                $originalText->tag = $tag;
                $originalText->lang = 'en';

                $original = self::$persistentTranslations->findByExample($originalText);
                if ($debug)
                  _log('Original: ' . json_encode($original) . "\n");
                if ($debug)
                  _log('  id: ' . $original['id'] . "\n");

                if (false == $original || empty($original['text'])) {
                  if (null != $DOMText && 0 < strlen($DOMText)) {
                  }
                  if (null != $DOMText && 0 < strlen($DOMText)) {
                    /**
                     * TODO
                     *   Check the operational mode is development or at least "not-production"
                     */
                    if ($debug)
                      _log("DOMText: $DOMText\n");
                    $originalText->text = trim(html_entity_decode($DOMText, ENT_QUOTES, 'UTF-8'));
                    if ($debug)
                      _log('Text to be saved: ' . $originalText->text);
                    $originalText->id = \YeAPF\generateUniqueId();
                    if ($debug)
                      _log('Saving original version');
                    self::$persistentTranslations->setDocument($originalText->id, $originalText);
                    if ($debug)
                      _log('Saved text: ' . $originalText->text);
                    $original = clone $originalText;
                  } else {
                    $translationRequired = false;
                  }
                }

                if ($translationRequired) {
                  if ('watson' == self::$config->model) {
                    $wordCount = str_word_count($original->text ?? '');

                    if ($debug)
                      _log("Translation required\n");
                    if ($debug)
                      _log("Current 'original' state " . json_encode($original) . "\n");
                    if ($debug)
                      _log("'original' id: '" . $original->id . "'\n");
                    if ($debug)
                      _log("'original' text: '" . $original->text . "'\n");
                    if ($debug)
                      _log('Words to be translated: ' . $wordCount . "\n");
                    $url = self::$config->url;
                    $apikey = self::$config->apikey;

                    if (0 < $wordCount) {
                      $auxText = trim(html_entity_decode($original->text, ENT_QUOTES, 'UTF-8'));

                      if ($debug)
                        _log("Text to be translated: $auxText\n");
                      self::loadFromAssets($translatedText);
                      if (is_null($translatedText->text) || 0 == strlen($translatedText->text)) {
                        $watson = \YeAPF\Request::do($url, 'POST', [
                          'text' => [$auxText],
                          'model_id' => 'en-' . $targetLang,
                        ], [
                          'username' => 'apikey',
                          'password' => $apikey,
                        ]);

                        if ($debug)
                          _log(var_dump($watson));

                        $translatedPhrase = '';
                        $aux = json_decode($watson, true);
                        $trans = $aux['translations'];
                        foreach ($trans as $line) {
                          if ($translatedPhrase > '') {
                            $translatedPhrase .= "\n";
                          }
                          $translatedPhrase .= $line['translation'];
                        }

                        $result['translated'] = true;
                        $translatedText->text = $translatedPhrase;
                        $result['cached'] = false;
                      } else {
                        if ($debug)
                          _log('Using translation saved on assets: ' . $translatedText->text);
                        $translatedPhrase = $translatedText->text;
                        $result['cached'] = true;
                      }

                      $translatedText->id = \YeAPF\generateUniqueId();
                      self::$persistentTranslations->setDocument($translatedText->id, $translatedText);
                      $result['text'] = $translatedPhrase;

                      $originalText->text = $auxText;
                      self::saveIntoAssets($originalText);
                      self::saveIntoAssets($translatedText);
                    } else {
                      if ($debug)
                        _log("No text to be translated\n");
                      $result['text'] = 'No text to be translated';
                    }
                  }
                }
              }
            } catch (\Throwable $th) {
              throw new \YeAPF\YeAPFException($th->getMessage(), YeAPF_PLUGIN_ERROR);
            }
            if ($debug)
              _log('Translation result: ' . print_r($result, true) . "\n");
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

  public function registerServiceMethods($server) {}
}
