<?php declare(strict_types=1);

namespace YeAPF;

class YeAPFConsole
{
  private static $width = null;
  private static $height = null;

  public static function isTTY()
  {
    return php_sapi_name() === 'cli';
  }

  private function isDocker(): bool
  {
    return is_file('/.dockerenv');
  }

  public static function initialize()
  {
    if (self::isTTY() && function_exists('\ncurses_init')) {
      \ncurses_init();
      self::$width = ncurses_cols();
      self::$height = ncurses_lines();
      \ncurses_close();
    } else {
      if (self::isTTY()) {
        $aux = intval(exec('tput cols>/dev/null 2>&1'));
        $aux = (int) ($aux > 0) ? $aux : 80;
        self::$width = $aux;

        $aux = intval(exec('tput lines>/dev/null 2>&1'));
        $aux = (int) ($aux > 0) ? $aux : 24;
        self::$height = $aux;
      } else {
        self::$width = 80;
        self::$height = 24;
      }
    }

    self::$width = intval(self::$width);
    self::$height = intval(self::$height);
  }

  public static function getWidth()
  {
    if (null == self::$width) {
      self::initialize();
    }
    return self::$width;
  }

  public static function getHeight()
  {
    if (null == self::$height) {
      self::initialize();
    }
    return self::$height;
  }
}

YeAPFConsole::initialize();

function dec2hex($dec, $len = 8): string
{
  $hex = str_pad(dechex((int) $dec), $len ?? 8, '0', STR_PAD_LEFT);
  return '0x' . $hex;
}

/**
 * It generates a UUIDv4
 *
 * @return string
 */
function generateUUIDv4(): string
{
  if (function_exists('random_bytes')) {
    $data = random_bytes(16);
  } elseif (function_exists('openssl_random_pseudo_bytes')) {
    $data = openssl_random_pseudo_bytes(16);
  } else {
    $data = uniqid('', true);
  }

  $data[6] = chr(ord($data[6]) & 0x0F | 0x40);
  $data[8] = chr(ord($data[8]) & 0x3F | 0x80);

  $uuid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));

  return $uuid;
}

/**
 * It generates an UUIDv5 using the namespace and the
 * hostname to identify this node
 *
 * @return string
 */
function generateUUIDv5(): string
{
  $ret = false;
  $namespace = \YeAPF\YeAPFConfig::getSection('randomness')->namespace;
  if (is_string($namespace)) {
    $namespace = strtolower($namespace);
    if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $namespace)) {
      $namespaceUUID = hex2bin(str_replace(['-', '{', '}'], '', $namespace));

      $name = gethostname();

      $hash = sha1($namespaceUUID . $name, true);
      $hash[6] = chr(ord($hash[6]) & 0x0F | 0x50);
      $hash[8] = chr(ord($hash[8]) & 0x3F | 0x80);

      $ret = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($hash), 4));
    } else {
      throw new \YeAPF\YeAPFException("Invalid namespace: '$namespace'", YeAPF_INVALID_SPACE_NAME);
    }
  } else {
    throw new \YeAPF\YeAPFException('Namespace must be a string', YeAPF_INVALID_SPACE_NAME);
  }

  return $ret;
}

/**
 * This generates a unique id using an UUIDv4 as base
 * and adding the timestamp and server id at the start
 * In such way, the id is unique within the namespace
 * and we can trace the id up to the time when the object
 * was created.
 *
 * @return string
 */
function generateUniqueId(): string
{
  $uuid = generateUUIDv4();
  // _log("$uuid");

  $timestamp = dechex(time());
  // _log("[$timestamp]");
  // _log(date("Y-m-d H:i:s", hexdec($timestamp)));

  $serverHash = sha1(gethostname(), true);
  $serverId = substr(bin2hex($serverHash), 0, 3);
  // _log("[$serverId]");

  $uuid = substr_replace($uuid, $timestamp, 0, 8);
  $uuid = substr_replace($uuid, $serverId, 9, 3);

  return $uuid;
}

function generateShortUniqueId(): string
{
  function __encodeTimeElement($element)
  {
    // Refactored code to use hours over the set [A-Z, a-z, 0-7]
    if ($element < 26) {
      $code = chr($element + ord('A'));
    } elseif ($element < 52) {
      $code = chr($element - 26 + ord('a'));
    } else {
      $code = chr($element - 52 + ord('0'));
    }
    return $code;
  }

  $yearCode = chr((int) date('Y') - 2024 + ord('A'));

  $weekOfYear = (int) date('W');
  $weekCode = __encodeTimeElement($weekOfYear);

  $dayOfWeekCode = (string) date('w');

  $hours = (int) date('H');
  $minutes = (int) date('i');
  $seconds = (int) date('s');

  $timeCode = __encodeTimeElement($hours) . __encodeTimeElement($minutes) . __encodeTimeElement($seconds);

  $serverHash = sha1(gethostname(), true);
  $serverId = substr(bin2hex($serverHash), 0, 3);

  $randomString = substr(bin2hex(random_bytes(4)), 0, 3); 

  $ret = $yearCode . $weekCode . $dayOfWeekCode . $timeCode . $serverId . $randomString;
  return $ret;
}

function validatePassword(string $password): bool
{
  $hasMinimumLength = strlen($password) >= 8;
  $hasMixedCharacters = preg_match('/[a-zA-Z]/', $password) && preg_match('/\d/', $password);
  $hasUpperCase = preg_match('/[A-Z]/', $password);
  $hasLowerCase = preg_match('/[a-z]/', $password);
  $hasSymbol = preg_match('/[!@#$%^&*()]/', $password);

  return $hasMinimumLength && $hasMixedCharacters && $hasUpperCase && $hasLowerCase && $hasSymbol;
}

function generateStrongPassword(int $length, int $strength): string
{
  $characters = '';

  if ($strength >= 1) {
    $characters .= '0123456789';
  }

  if ($strength >= 2) {
    $characters .= 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_';
    if ($length < 8) {
      $length = 8;
    }
  }

  if ($strength >= 3) {
    $characters .= '!@#$%^&*()';
    if ($length < 12) {
      $length = 12;
    }
  }

  $password = '';
  do {
    $password = '';
    for ($i = 0; $i < $length; $i++) {
      $randomIndex = random_int(0, strlen($characters) - 1);
      $password .= $characters[$randomIndex];
    }
  } while (validatePassword($password) == false);

  return $password;
}
