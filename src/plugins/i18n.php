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
    parent::__construct(__FILE__);
    if (!is_dir(self::getAssetsFolder())) {
      mkdir(self::getAssetsFolder(), 0777, true);
    }
    if (!is_writable(self::getAssetsFolder())) {
      throw new \YeAPF\YeAPFException('Assets folder ' . self::getAssetsFolder() . ' is not writable', YeAPF_ASSETS_FOLDER_NOT_WRITABLE);
    }
    self::$config  = \YeAPF\YeAPFConfig::getSection('i18n');
    self::$context = new \YeAPF\Connection\PersistenceContext(
      new \YeAPF\Connection\DB\RedisConnection(),
      new \YeAPF\Connection\DB\PDOConnection()
    );
    $this->grantStructure();
  }

  private function saveIntoAssets(\YeAPF\SanitizedKeyData $data)
  {
    $filename = self::getAssetsFolder() . '/' . $data->tag . '/' . $data->lang . '.txt';
    if (!is_dir(dirname($filename)))
      mkdir(dirname($filename), 0777, true);
    if (!is_writable(dirname($filename)))
      file_put_contents($filename, $data->text);
  }

  private function loadFromAssets(\YeAPF\SanitizedKeyData $data)
  {
    $filename = self::getAssetsFolder() . '/' . $data->tag . '/' . $data->lang . '.txt';
    if (file_exists($filename))
      $data->text = file_get_contents($filename);
  }

  private function loadCachedTranslation(\YeAPF\SanitizedKeyData $translatedText): ?string
  {
    self::loadFromAssets($translatedText);
    $cached = $translatedText->text ?? null;
    if (is_string($cached) && strlen($cached) > 0) {
      return $cached;
    }
    return null;
  }

  private function saveCachedTranslation(
    \YeAPF\SanitizedKeyData $originalText,
    \YeAPF\SanitizedKeyData $translatedText,
    string $sourceText
  ): void {
    $translatedText->id = \YeAPF\generateUniqueId();
    self::$persistentTranslations->setDocument($translatedText->id, $translatedText);

    $originalText->text = $sourceText;
    if (mb_strtolower($originalText->tag) <> mb_strtolower($originalText->text)) {
      self::saveIntoAssets($originalText);
    }
    if (mb_strtolower($translatedText->tag) <> mb_strtolower($translatedText->text)) {
      self::saveIntoAssets($translatedText);
    }
  }

  private function callTranslationAPI(string $text, string $targetLang): ?string
  {
    if (!in_array(self::$config->model, ['watson', 'libretranslate'])) {
      return null;
    }

    $url    = self::$config->url;
    $apikey = self::$config->apikey;
    if (self::$config->model == 'watson') {
      $payload = [
        'text'     => [$text],
        'model_id' => 'en-' . $targetLang,
      ];
      $auth = [
        'username' => 'apikey',
        'password' => $apikey,
      ];
    } else {
      $payload = [
        'q'      => $text,
        'source' => 'auto',
        'target' => $targetLang,
      ];
      $auth = [];
    }

    $response = \YeAPF\Request::do(
      $url,
      'POST',
      $payload,
      $auth,
      ['Content-Type: application/json']
    );

    $aux = json_decode($response, true);
    if (self::$config->model == 'watson') {
      $translatedPhrase = '';
      foreach (($aux['translations'] ?? []) as $line) {
        if ($translatedPhrase > '') {
          $translatedPhrase .= "\n";
        }
        $translatedPhrase .= $line['translation'] ?? '';
      }
      return $translatedPhrase;
    }

    return $aux['translatedText'] ?? null;
  }

  private function translateTag(
    string $tag,
    ?string $scope,
    string $targetLang,
    ?string $DOMText,
    Channel $channel,
    array &$ret
  ): void {
    $channel->push(true);
    try {
      $result = [];
      $tag = trim($tag);
      if (0 === strlen($tag)) {
        return;
      }

      $translatedText = clone self::$persistentTranslations->getDocumentModel();
      $translatedText->tag  = $tag;
      $translatedText->lang = $targetLang;

      $alreadyTranslated = self::$persistentTranslations->findByExample($translatedText);
      if ($alreadyTranslated && !empty($alreadyTranslated['text'])) {
        $result['text']   = $alreadyTranslated['text'];
        $result['cached'] = true;
        $ret[$tag] = $result;
        return;
      }

      $translationRequired = ($targetLang != 'en');
      $originalText       = clone self::$persistentTranslations->getDocumentModel();
      $originalText->tag  = $tag;
      $originalText->lang = 'en';

      $original = self::$persistentTranslations->findByExample($originalText);
      if (false == $original || empty($original['text'])) {
        if (null != $DOMText && 0 < strlen($DOMText)) {
          $originalText->text = trim(html_entity_decode($DOMText, ENT_QUOTES, 'UTF-8'));
          $originalText->id = \YeAPF\generateUniqueId();
          self::$persistentTranslations->setDocument($originalText->id, $originalText);
          $original = clone $originalText;
        } else {
          $translationRequired = false;
        }
      }

      if (!$translationRequired) {
        $ret[$tag] = $result;
        return;
      }

      $wordCount = str_word_count($original->text ?? '');
      if (0 >= $wordCount) {
        $result['text'] = 'No text to be translated';
        $ret[$tag] = $result;
        return;
      }

      $sourceText = trim(html_entity_decode($original->text, ENT_QUOTES, 'UTF-8'));
      $cachedTranslation = $this->loadCachedTranslation($translatedText);
      if (null !== $cachedTranslation) {
        $result['text']   = $cachedTranslation;
        $result['cached'] = true;
        $ret[$tag] = $result;
        return;
      }

      $translatedPhrase = $this->callTranslationAPI($sourceText, $targetLang) ?? '';
      $result['translated'] = true;
      $result['cached'] = false;
      $result['text'] = $translatedPhrase;
      $translatedText->text = $translatedPhrase;

      $this->saveCachedTranslation($originalText, $translatedText, $sourceText);
      $ret[$tag] = $result;
    } catch (\Throwable $th) {
      throw new \YeAPF\YeAPFException($th->getMessage(), YeAPF_PLUGIN_ERROR);
    } finally {
      $channel->pop();
    }
  }

  public function translate(
    string $scope      = null,
    string $targetLang = null,
    string $tag        = null,
    string $DOMText    = null
  ) {
    $ret = [];

    if (strpos($targetLang, '-') !== false) {
      $targetLang = substr($targetLang, 0, strpos($targetLang, '-'));
    }

    $tags = explode(':', $tag ?? '');
    $channel = new Channel(count($tags));

    foreach ($tags as $auxTag) {
      Coroutine::create(function () use ($auxTag, $scope, $targetLang, $DOMText, $channel, &$ret) {
        $this->translateTag($auxTag, $scope, $targetLang, $DOMText, $channel, $ret);
      });
    }

    while (!$channel->isEmpty()) {
      Coroutine::yield();
    }

    return $ret;
  }

  public function registerServiceMethods($server) {}
}
