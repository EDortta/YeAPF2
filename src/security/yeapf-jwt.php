<?php declare(strict_types=1);

namespace YeAPF\Security;

use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

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

    public function __construct($aToken = null)
    {
        $this->secretKey = \YeAPF\YeAPFConfig::getSection('randomness')->jwtKey;
        $this->algorithm = 'HS256';
        $this->exp = time() + 1800;
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
            \_trace("Cleaning $folder");
            $weekAgo = strtotime('-1 week');
            foreach (scandir($folder) as $file) {
                if (strpos($file, '.jwt') !== false) {
                    $filePath = $folder . '/' . $file;
                    if (filemtime($filePath) < $weekAgo) {
                        unlink($filePath);
                        \_trace("Deleted $file");
                    }
                }
            }
        }
    }

    public function sendToBin($token = null)
    {
        \_trace("Discarding $token");
        if (null == $token || strlen($token) == 0) {
            $token = $this->jwtToken ?? '';
        }

        if (strlen($token) > 0) {
            $folder = self::getAssetsFolder();
            if (is_dir($folder)) {
                \_trace("Sending $token to $folder");
                $filePath = $folder . '/' . md5($token) . '.jwt';
                file_put_contents($filePath, $token);
            } else {
                \_trace("Cannot send $token to $folder");
            }
        } else {
            \_trace("Cannot send '$token' to $folder. It's empty.");
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
        \_trace('Token in bin: ' . ($ret ? 'yes' : 'no'));
        return $ret;
    }

    private function importToken($aToken)
    {
        $this->importResult = null;

        [$header, $payload, $signature] = explode('.', $aToken);
        $decodedPayload = base64_decode($payload);
        $payloadArray = json_decode($decodedPayload, true);
        \_trace('Token payload: ' . print_r($payloadArray, true));

        $this->exp = $payloadArray['exp'];
        $currentTime = time();
        \_trace(" exp: $this->exp");
        \_trace('time: ' . $currentTime);
        \_trace('diff: ' . ($this->exp - $currentTime));

        if (!$this->tokenInBin($aToken)) {
            $this->importResult = false;
            if ($this->exp >= $currentTime) {
                $algo = $this->algorithm;
                \_trace("TOKEN: $aToken");
                \_trace("ALGORITHM: $algo");
                \_trace("SECRET KEY: $this->secretKey");
                try {
                    $decoded = JWT::decode($aToken, new \Firebase\JWT\Key($this->secretKey, $algo));
                    foreach ($this->registeredClaimNames as $claimName) {
                        if (isset($decoded->$claimName))
                            $this->$claimName = $decoded->$claimName;
                    }
                    $this->importResult = YeAPF_JWT_SIGNATURE_VERIFICATION_OK;
                    $this->jwtToken = $aToken;
                } catch (\Exception $e) {
                    \_trace('Exception: ' . $e->getMessage());
                    $this->importResult = YeAPF_JWT_SIGNATURE_VERIFICATION_FAILED;
                    $this->sendToBin($aToken);
                }
            } else {
                \_trace('Token expired');
                $this->importResult = YeAPF_JWT_EXPIRED;
            }
        } else {
            \_trace('Token already in bin');
            $this->importResult = YeAPF_JWT_ALREADY_IN_BIN;
        }
        \_trace('Import Result: ('. json_encode($this->importResult) . ') ' . $this->explainImportResult());
        return $this->importResult;
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

        $this->iat = time();
        if (empty($this->exp))
            $this->exp = $this->iat + $this->timeToLive;
        $this->aud = $this->getAllowedSections();
        $this->jwtToken = JWT::encode($this->getPayload(), $this->secretKey, $this->algorithm);
        return $this->jwtToken;
    }
}
