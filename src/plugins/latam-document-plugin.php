<?php declare(strict_types=1);

final class LatamDocumentPlugin implements \YeAPF\Plugins\Validator\DocumentValidatorInterface, \YeAPF\Plugins\Type\TypeProviderInterface
{
    /**
     * @return list<string>
     */
    public function getSupportedKeys(): array
    {
        return ['UY.CI', 'AR.DNI', 'PE.DNI'];
    }

    public function validate(string $key, string $value): bool
    {
        return match ($key) {
            'UY.CI' => $this->validateUY($value),
            'AR.DNI' => $this->validateAR($value),
            'PE.DNI' => $this->validatePE($value),
            default => false,
        };
    }

    /**
     * @return array<string,array<string,mixed>>
     */
    public function getTypeDefinitions(): array
    {
        return [
            'UY_CI' => [
                'type' => YeAPF_TYPE_STRING,
                'length' => 8,
                'acceptNULL' => false,
                'regExpression' => '/^\d{8}$/',
                'sedInputExpression' => '/[^0-9]//',
                'authenticityChecker' => 'UY.CI',
                'unique' => false,
                'required' => false,
                'primary' => false,
                'tag' => ';;',
            ],
            'AR_DNI' => [
                'type' => YeAPF_TYPE_STRING,
                'length' => 8,
                'acceptNULL' => false,
                'regExpression' => '/^\d{8}$/',
                'sedInputExpression' => '/[^0-9]//',
                'authenticityChecker' => 'AR.DNI',
                'unique' => false,
                'required' => false,
                'primary' => false,
                'tag' => ';;',
            ],
            'PE_DNI' => [
                'type' => YeAPF_TYPE_STRING,
                'length' => 9,
                'acceptNULL' => false,
                'regExpression' => '/^[0-9A-Za-z]{8,9}$/',
                'sedInputExpression' => '/[^0-9A-Za-z]//',
                'authenticityChecker' => 'PE.DNI',
                'unique' => false,
                'required' => false,
                'primary' => false,
                'tag' => ';;',
            ],
        ];
    }

    private function validateUY(string $id): bool
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

        $calculatedDigit = 0 === ($sum % 10) ? 0 : 10 - ($sum % 10);

        return $calculatedDigit === $validationDigit;
    }

    private function validateAR(string $id): bool
    {
        $digits = preg_replace('/\D/', '', $id) ?? '';

        if (8 !== strlen($digits)) {
            return false;
        }

        if ('0' === $digits[0]) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 7; $i++) {
            $sum += ((int) $digits[$i]) * (2 + ($i % 6));
        }

        $verificationDigit = (11 - ($sum % 11)) % 11;

        return ((int) $digits[7]) === $verificationDigit;
    }

    private function validatePE(string $id): bool
    {
        $dni = strtoupper(trim(str_replace('-', '', $id)));
        if ('' === $dni || strlen($dni) < 9) {
            return false;
        }

        $multiples = [3, 2, 7, 6, 5, 4, 3, 2];
        $numberControls = [6, 7, 8, 9, 0, 1, 1, 2, 3, 4, 5];
        $letterControls = ['K', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'];

        $numericPart = str_split(substr($dni, 0, -1));
        $control = substr($dni, -1);

        $sum = 0;
        foreach ($numericPart as $index => $digit) {
            if (!isset($multiples[$index]) || !ctype_digit($digit)) {
                return false;
            }
            $sum += ((int) $digit) * $multiples[$index];
        }

        $key = 11 - ($sum % 11);
        $controlIndex = (11 === $key) ? 0 : $key;

        if (ctype_digit($dni)) {
            return isset($numberControls[$controlIndex]) && $numberControls[$controlIndex] === (int) $control;
        }

        return isset($letterControls[$controlIndex]) && $letterControls[$controlIndex] === $control;
    }
}

$plugin = new LatamDocumentPlugin();
\YeAPF\Plugins\Registry::registerDocumentValidator($plugin);
\YeAPF\Plugins\Registry::registerTypeProvider($plugin);
