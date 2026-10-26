<?php declare(strict_types=1);

namespace YeAPF\Security;

class yJWT extends \YeAPF\SanitizedKeyData
{
    use \YeAPF\Assets;

    private $secretKey;
    private $algorithm;
    private $allowedSections;
    /**
     * https://datatracker.ietf.org/doc/html/rfc7519#page-9
     *
     * uot is for YeAPF applications usage meaning "Use One Time Token"
     * These kind of token will be discarded after the first well succeded use
     */
    private $registeredClaimNames = ['iss', 'sub', 'aud', 'nbf', 'exp', 'iat', 'jti', 'uot', 'key'];
    private $timeToLive;
    private $jwtToken;
    private $importResult;
    /** @var null|callable():int */
    private static $clock = null;

    public function __construct($aToken = null)
    {
        $this->secretKey = \YeAPF\YeAPFConfig::getSection('randomness')->jwtKey;
        $this->algorithm = 'HS256';
        $this->exp = self::now() + 1800;
        $this->allowedSections = [];
        $this->jwtToken = null;
        $this->importResult = null;

        if ($aToken) {
            $this->importToken($aToken);
            $this->cleanBin();
        }
    }

    public function getImportResult()
    {
        return $this->importResult;
    }

    public function explainImportResult()
    {
        $ret = '';
        switch ($this->importResult) {
            case null:
                $ret = 'Token was not imported';
                break;

            case false:
                $ret = 'Token could not be imported';
                break;

            case YeAPF_JWT_SIGNATURE_VERIFICATION_OK:
                $ret = 'Token was verified and imported';
                break;

            case YeAPF_JWT_SIGNATURE_VERIFICATION_FAILED:
                $ret = 'Token could not be verified';
                break;

            case YeAPF_JWT_EXPIRED:
                $ret = 'Token expired';
                break;

            case YeAPF_JWT_ALREADY_IN_BIN:
                $ret = 'Token already in bin';
                break;

            default:
                $ret = 'Unknown';
                break;
        }
        return $ret;
    }

    public static function getAssetsFolder(): string
    {
        $configFolder = self::getApplicationFolder() . '/assets/jwt-bin';

        if (is_dir($configFolder)) {
            $configFolder = realpath($configFolder);
        }

        return $configFolder;
    }

    public static function canWorkWithoutAssets(): bool
    {
        return false;
    }

    private function cleanBin()
    {
        $folder = self::getAssetsFolder();
        if (is_dir($folder)) {
            $weekAgo = strtotime('-1 week');
            foreach (scandir($folder) as $file) {
                if (strpos($file, '.jwt') !== false) {
                    $filePath = $folder . '/' . $file;
                    if (filemtime($filePath) < $weekAgo) {
                        unlink($filePath);
                    }
                }
            }
        }
    }

    public function sendToBin($token = null)
    {
        if (null == $token || strlen($token) == 0) {
            $token = $this->jwtToken ?? '';
        }

        if (strlen($token) > 0) {
            $folder = self::getAssetsFolder();
            if (is_dir($folder)) {
                $filePath = $folder . '/' . md5($token) . '.jwt';
                file_put_contents($filePath, $token);
            }
        }
    }

    public function tokenInBin($token=null): bool
    {
        $ret = false;
        if (null == $token || strlen($token) == 0) {
            $token = $this->jwtToken ?? '';
        }

        $folder = self::getAssetsFolder();
        if (is_dir($folder)) {
            $filePath = $folder . '/' . md5($token) . '.jwt';
            $ret = file_exists($filePath);
        }
        return $ret;
    }

    /** @param null|callable():int $clock */
    public static function setClock(?callable $clock): void
    {
        self::$clock = $clock;
    }

    private static function now(): int
    {
        return self::$clock === null ? time() : (int) (self::$clock)();
    }

    private function importToken($aToken)
    {
        $this->importResult = false;
        if (!is_string($aToken) || $aToken === '') {
            return $this->importResult;
        }
        if ($this->tokenInBin($aToken)) {
            $this->importResult = YeAPF_JWT_ALREADY_IN_BIN;
            return $this->importResult;
        }

        try {
            $payloadArray = $this->decodeToken($aToken);
            $currentTime = self::now();
            $expiresAt = isset($payloadArray['exp']) ? (int) $payloadArray['exp'] : null;
            if ($expiresAt !== null && $expiresAt <= $currentTime) {
                $this->importResult = YeAPF_JWT_EXPIRED;
                return $this->importResult;
            }
            if (isset($payloadArray['nbf']) && (int) $payloadArray['nbf'] > $currentTime) {
                $this->importResult = YeAPF_JWT_SIGNATURE_VERIFICATION_FAILED;
                return $this->importResult;
            }

            foreach ($this->registeredClaimNames as $claimName) {
                if (array_key_exists($claimName, $payloadArray)) {
                    $this->$claimName = $payloadArray[$claimName];
                }
            }
            $this->importResult = YeAPF_JWT_SIGNATURE_VERIFICATION_OK;
            $this->jwtToken = $aToken;
        } catch (\Throwable) {
            $this->importResult = YeAPF_JWT_SIGNATURE_VERIFICATION_FAILED;
            $this->sendToBin($aToken);
        }

        return $this->importResult;
    }

    /** @return array<string, mixed> */
    private function decodeToken(string $token): array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new \UnexpectedValueException('Malformed JWT.');
        }

        [$encodedHeader, $encodedPayload, $encodedSignature] = $parts;
        $header = json_decode(self::base64UrlDecode($encodedHeader), true, 512, JSON_THROW_ON_ERROR);
        $payload = json_decode(self::base64UrlDecode($encodedPayload), true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($header) || ($header['alg'] ?? null) !== $this->algorithm || !is_array($payload)) {
            throw new \UnexpectedValueException('Unsupported JWT header or payload.');
        }

        $expected = hash_hmac('sha256', $encodedHeader . '.' . $encodedPayload, $this->secretKey, true);
        $actual = self::base64UrlDecode($encodedSignature);
        if (!hash_equals($expected, $actual)) {
            throw new \UnexpectedValueException('Invalid JWT signature.');
        }

        return $payload;
    }

    private static function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $value): string
    {
        $padding = strlen($value) % 4;
        if ($padding > 0) {
            $value .= str_repeat('=', 4 - $padding);
        }
        $decoded = base64_decode(strtr($value, '-_', '+/'), true);
        if ($decoded === false) {
            throw new \UnexpectedValueException('Invalid JWT encoding.');
        }
        return $decoded;
    }

    public function addAllowedSection($section)
    {
        if (is_string($section)) {
            $section = strtolower(trim($section));
            if (strlen($section) > 0) {
                if (strlen($section) < 5) {
                    if (!in_array($section, $this->allowedSections))
                        $this->allowedSections[] = $section;
                } else {
                    throw new \YeAPF\YeAPFException('Section identifier too long', YeAPF_JWT_SECTION_IDENTIFIER_TOO_LONG);
                }
            } else {
                throw new \YeAPF\YeAPFException('Section identifier empty', YeAPF_JWT_SECTION_IDENTIFIER_EMPTY);
            }
        } else {
            foreach ($section as $s) {
                $this->addAllowedSection($s);
            }
        }
    }

    public function checkAllowedSection($section)
    {
        $section = strtolower(trim($section));
        return in_array($section, $this->allowedSections);
    }

    public function getAllowedSections()
    {
        return $this->allowedSections;
    }

    public function addPayloadItem($key, $value)
    {
        $key = strtolower(trim($key));
        $this->$key = $value;
    }

    public function getPayload()
    {
        return array_merge(
            $this->exportData()
        );
    }

    public function createToken()
    {
        if (empty($this->secretKey))
            throw new \YeAPF\YeAPFException('Missing JWT secret key', YeAPF_JWT_KEY_UNDEFINED);

        $this->iat = self::now();
        if (empty($this->exp)) {
            $this->exp = $this->iat + ($this->timeToLive ?? 1800);
        }
        $this->aud = $this->getAllowedSections();
        $header = self::base64UrlEncode(json_encode(['typ' => 'JWT', 'alg' => $this->algorithm], JSON_THROW_ON_ERROR));
        $payload = self::base64UrlEncode(json_encode($this->getPayload(), JSON_THROW_ON_ERROR));
        $signature = hash_hmac('sha256', $header . '.' . $payload, $this->secretKey, true);
        $this->jwtToken = $header . '.' . $payload . '.' . self::base64UrlEncode($signature);
        return $this->jwtToken;
    }
}
