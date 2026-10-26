<?php

declare(strict_types=1);

namespace Tests;

use ReflectionProperty;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use YeAPF\Security\yJWT;
use YeAPF\YeAPFConfig;
use YeAPF\i18n\Translator;
use YeAPF\yLogger;

#[Group('unit')]
final class SecurityObservabilityBehaviorTest extends TestCase
{
    private const NOW = 2_000_000_000;
    private const SECRET = 'unit-test-secret-not-a-real-credential';

    /** @var array<string, mixed> */
    private array $previousConfig = [];
    /** @var array<string, mixed> */
    private array $previousServer = [];
    private string $temporaryRoot = '';

    protected function setUp(): void
    {
        $property = new ReflectionProperty(YeAPFConfig::class, 'configAreas');
        $value = $property->getValue();
        $this->previousConfig = is_array($value) ? $value : [];
        $property->setValue(null, ['randomness' => (object) ['jwtKey' => self::SECRET]]);
        $this->previousServer = $_SERVER;
        $this->temporaryRoot = sys_get_temp_dir() . '/y2-security-' . bin2hex(random_bytes(4));
        mkdir($this->temporaryRoot . '/assets/jwt-bin', 0775, true);
        $_SERVER['SCRIPT_FILENAME'] = $this->temporaryRoot . '/index.php';
        yJWT::setClock(static fn (): int => self::NOW);
    }

    protected function tearDown(): void
    {
        yJWT::setClock(null);
        (new ReflectionProperty(YeAPFConfig::class, 'configAreas'))->setValue(null, $this->previousConfig);
        $_SERVER = $this->previousServer;
        yLogger::setLogFacility(0);
        yLogger::defineLogFilters(YeAPF_LOG_EMERG + 100);
        $this->removeDirectory($this->temporaryRoot);
    }

    public function testJwtValidSignatureAndClaimsRoundTrip(): void
    {
        $jwt = new yJWT();
        $jwt->addAllowedSection(['api', 'adm']);
        $jwt->addPayloadItem('iss', 'test-suite');
        $jwt->addPayloadItem('sub', 'user-1');
        $token = $jwt->createToken();

        $imported = new yJWT($token);

        $this->assertSame(YeAPF_JWT_SIGNATURE_VERIFICATION_OK, $imported->getImportResult());
        $this->assertSame('test-suite', $imported->getPayload()['iss']);
        $this->assertSame(['api', 'adm'], $imported->getPayload()['aud']);
        $this->assertSame(self::NOW, $imported->getPayload()['iat']);
    }

    public function testJwtRejectsTamperedSignatureWithoutLeakingSecret(): void
    {
        $token = (new yJWT())->createToken();
        [$header, $payload, $signature] = explode('.', $token);
        $signature[0] = $signature[0] === 'A' ? 'B' : 'A';

        ob_start();
        $imported = new yJWT($header . '.' . $payload . '.' . $signature);
        $output = (string) ob_get_clean();

        $this->assertSame(YeAPF_JWT_SIGNATURE_VERIFICATION_FAILED, $imported->getImportResult());
        $this->assertStringNotContainsString(self::SECRET, $output);
    }

    public function testJwtRejectsExpiredNotBeforeAndIncompatibleAlgorithmClaims(): void
    {
        $expired = new yJWT();
        $expired->addPayloadItem('exp', self::NOW - 1);
        $this->assertSame(YeAPF_JWT_EXPIRED, (new yJWT($expired->createToken()))->getImportResult());

        $future = new yJWT();
        $future->addPayloadItem('nbf', self::NOW + 60);
        $this->assertSame(
            YeAPF_JWT_SIGNATURE_VERIFICATION_FAILED,
            (new yJWT($future->createToken()))->getImportResult()
        );

        $parts = explode('.', (new yJWT())->createToken());
        $parts[0] = $this->base64Url(json_encode(['typ' => 'JWT', 'alg' => 'HS512'], JSON_THROW_ON_ERROR));
        $parts[2] = $this->base64Url(hash_hmac('sha256', $parts[0] . '.' . $parts[1], self::SECRET, true));
        $this->assertSame(
            YeAPF_JWT_SIGNATURE_VERIFICATION_FAILED,
            (new yJWT(implode('.', $parts)))->getImportResult()
        );
    }

    public function testLoggerHonorsLevelAndRedactsStructuredSecretsWithoutHttpOutput(): void
    {
        yLogger::setLogFacility(YeAPF_LOG_USING_CONSOLE);
        yLogger::defineLogFilters(YeAPF_LOG_DEBUG);

        ob_start();
        yLogger::log(0, YeAPF_LOG_INFO, '{"token":"abc123","message":"ok"} password=hunter2');
        $output = (string) ob_get_clean();

        $this->assertStringContainsString('[REDACTED]', $output);
        $this->assertStringNotContainsString('abc123', $output);
        $this->assertStringNotContainsString('hunter2', $output);
        $this->assertSame([], headers_list());
    }

    public function testLoggerUnavailableDestinationDoesNotEchoOrThrow(): void
    {
        $property = new ReflectionProperty(yLogger::class, 'logFolder');
        $previous = $property->getValue();
        $property->setValue(null, '/proc/y2-unwritable/logs');
        yLogger::setLogFacility(YeAPF_LOG_USING_FILE);
        yLogger::defineLogFilters(YeAPF_LOG_DEBUG);

        $bufferLevel = ob_get_level();
        try {
            ob_start();
            yLogger::log(0, YeAPF_LOG_INFO, 'message');
            $this->assertSame('', (string) ob_get_clean());
        } finally {
            while (ob_get_level() > $bufferLevel) {
                ob_end_clean();
            }
            $property->setValue(null, $previous);
        }
    }

    public function testI18nFallbackMissingKeyAndSafeInterpolation(): void
    {
        $catalog = [
            'en' => ['welcome' => 'Welcome, {name}!'],
            'pt' => ['welcome' => 'Olá, {name}!'],
        ];

        $this->assertSame(
            'Olá, Ana!',
            Translator::resolve($catalog, 'welcome', 'pt-BR', replacements: ['name' => 'Ana'])
        );
        $this->assertSame(
            'Welcome, Ana!',
            Translator::resolve($catalog, 'welcome', 'fr', replacements: ['name' => 'Ana'])
        );
        $this->assertSame('unknown', Translator::resolve($catalog, 'unknown', null));
        $this->assertSame(
            'Olá, &lt;script&gt;!',
            Translator::resolve($catalog, 'welcome', 'pt', replacements: ['name' => '<script>'])
        );
    }

    private function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }
        foreach (scandir($directory) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $directory . '/' . $item;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($directory);
    }
}
