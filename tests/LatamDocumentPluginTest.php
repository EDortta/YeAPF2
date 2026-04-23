<?php declare(strict_types=1);

require_once __DIR__ . '/DocumentValidatorConformanceTestCase.php';

final class LatamDocumentPluginTest extends DocumentValidatorConformanceTestCase
{
    protected function supportedKeys(): array
    {
        return ['UY.CI', 'AR.DNI', 'PE.DNI'];
    }

    protected function validFixtures(): array
    {
        return [
            ['UY.CI', '34043626'],
            ['AR.DNI', '12345676'],
            ['PE.DNI', '84814845H'],
        ];
    }

    protected function invalidFixtures(): array
    {
        return [
            ['UY.CI', '92500986'],
            ['AR.DNI', '01234567'],
            ['PE.DNI', '1234-AB'],
        ];
    }

    protected function typeAuthenticityMap(): array
    {
        return [
            'UY_CI' => 'UY.CI',
            'AR_DNI' => 'AR.DNI',
            'PE_DNI' => 'PE.DNI',
        ];
    }

    protected function pluginClassName(): string
    {
        return 'LatamDocumentPlugin';
    }

    protected function pluginFilePath(): string
    {
        return __DIR__ . '/../src/plugins/latam-document-plugin.php';
    }

    public function testLegacyFixturesKeepSameResults(): void
    {
        $validator = \YeAPF\Plugins\Registry::getDocumentValidator('UY.CI');
        $this->assertNotNull($validator);

        $fixtures = [
            ['UY.CI', '34043626'],
            ['UY.CI', '79488918'],
            ['AR.DNI', '49077165H'],
            ['PE.DNI', '84814845E'],
            ['UY.CI', '92500986'],
            ['UY.CI', '29031009'],
        ];

        foreach ($fixtures as [$key, $value]) {
            $legacyResult = match ($key) {
                'UY.CI' => $this->legacyValidateUY($value),
                'AR.DNI' => $this->legacyValidateAR($value),
                'PE.DNI' => $this->legacyValidatePE($value),
                default => false,
            };
            $this->assertSame($legacyResult, $validator->validate($key, $value), $key . ':' . $value);
        }
    }

    public function testGlobalCustomerCheckerNoLongerExists(): void
    {
        $this->assertArrayNotHasKey('customerDocumentChecker', $GLOBALS);
    }

    private function legacyValidateUY(string $id): bool
    {
        $ci = preg_replace('/\D/', '', $id) ?? '';
        if ('' === trim($ci)) {
            return false;
        }

        $validationDigit = (int) substr($ci, -1);
        $ci = substr($ci, 0, -1);
        $ci = str_pad($ci, 7, '0', STR_PAD_LEFT);

        $sum = 0;
        $baseNumber = '2987634';
        for ($i = 0; $i < 7; $i++) {
            $sum += (((int) $baseNumber[$i]) * ((int) $ci[$i])) % 10;
        }
        $calculatedDigit = 0 === $sum % 10 ? 0 : 10 - $sum % 10;

        return $calculatedDigit === $validationDigit;
    }

    private function legacyValidateAR(string $id): bool
    {
        $digits = preg_replace('/\D/', '', $id) ?? '';
        if (8 !== strlen($digits)) {
            return false;
        }
        if ('0' === substr($digits, 0, 1)) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 7; $i++) {
            $sum += ((int) $digits[$i]) * (2 + ($i % 6));
        }
        $verificationDigit = (11 - ($sum % 11)) % 11;

        return ((int) $digits[7]) === $verificationDigit;
    }

    private function legacyValidatePE(string $id): bool
    {
        $dni = strtoupper(trim(str_replace('-', '', $id)));
        if ('' === $dni || strlen($dni) < 9) {
            return false;
        }

        $multiples = [3, 2, 7, 6, 5, 4, 3, 2];
        $controlsNumbers = [6, 7, 8, 9, 0, 1, 1, 2, 3, 4, 5];
        $controlsLetters = ['K', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'];
        $numdni = str_split(substr($dni, 0, -1));
        $dcontrol = substr($dni, -1);

        $dsum = array_reduce($numdni, static function ($acc, $digit) use ($multiples) {
            $acc += ((int) $digit) * array_shift($multiples);
            return $acc;
        }, 0);

        $key = 11 - ($dsum % 11);
        $index = (11 === $key) ? 0 : $key;
        if (is_numeric($dni)) {
            return $controlsNumbers[$index] === (int) $dcontrol;
        }

        return $controlsLetters[$index] === $dcontrol;
    }
}
