<?php declare(strict_types=1);

require_once __DIR__ . '/../src/yeapf-core.php';

use PHPUnit\Framework\TestCase;

final class LatamDocumentPluginTest extends TestCase
{
    public function testRegistryLoadsLatamDocumentValidator(): void
    {
        $uy = \YeAPF\Plugins\Registry::getDocumentValidator('UY.CI');
        $ar = \YeAPF\Plugins\Registry::getDocumentValidator('AR.DNI');
        $pe = \YeAPF\Plugins\Registry::getDocumentValidator('PE.DNI');

        $this->assertNotNull($uy);
        $this->assertNotNull($ar);
        $this->assertNotNull($pe);
        $this->assertSame($uy, $ar);
        $this->assertSame($ar, $pe);
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

    public function testTypeDefinitionsAreLoadedWithAuthenticityCheckers(): void
    {
        $uyType = \YeAPF\BasicTypes::get('UY_CI');
        $arType = \YeAPF\BasicTypes::get('AR_DNI');
        $peType = \YeAPF\BasicTypes::get('PE_DNI');

        $this->assertIsArray($uyType);
        $this->assertIsArray($arType);
        $this->assertIsArray($peType);

        $this->assertSame('UY.CI', $uyType['authenticityChecker'] ?? null);
        $this->assertSame('AR.DNI', $arType['authenticityChecker'] ?? null);
        $this->assertSame('PE.DNI', $peType['authenticityChecker'] ?? null);
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
