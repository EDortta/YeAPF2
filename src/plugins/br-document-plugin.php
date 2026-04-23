<?php declare(strict_types=1);

final class BrazilDocumentPlugin implements \YeAPF\Plugins\Validator\DocumentValidatorInterface, \YeAPF\Plugins\Type\TypeProviderInterface
{
    /**
     * @return list<string>
     */
    public function getSupportedKeys(): array
    {
        return ['BR.CNPJ', 'BR.CPF'];
    }

    public function validate(string $key, string $value): bool
    {
        $digits = preg_replace('/\D/', '', $value) ?? '';

        if ('BR.CNPJ' === $key) {
            return $this->validateCNPJ($digits);
        }

        if ('BR.CPF' === $key) {
            return $this->validateCPF($digits);
        }

        return false;
    }

    /**
     * @return array<string,array<string,mixed>>
     */
    public function getTypeDefinitions(): array
    {
        return [
            'CNPJ' => [
                'type' => YeAPF_TYPE_STRING,
                'length' => 14,
                'acceptNULL' => false,
                'regExpression' => '/^\d{14}$/',
                'sedInputExpression' => '/[^0-9]//',
                'sedOutputExpression' => '/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/$1.$2.$3\/$4-$5/',
                'authenticityChecker' => 'BR.CNPJ',
                'unique' => false,
                'required' => false,
                'primary' => false,
                'tag' => ';;',
            ],
            'CPF' => [
                'type' => YeAPF_TYPE_STRING,
                'length' => 11,
                'acceptNULL' => false,
                'regExpression' => '/^\d{11}$/',
                'sedInputExpression' => '/[^0-9]//',
                'sedOutputExpression' => '/(\d{3})(\d{3})(\d{3})(\d{2})/$1.$2.$3-$4/',
                'authenticityChecker' => 'BR.CPF',
                'unique' => false,
                'required' => false,
                'primary' => false,
                'tag' => ';;',
            ],
        ];
    }

    private function validateCNPJ(string $cnpj): bool
    {
        if (14 !== strlen($cnpj) || preg_match('/^(\d)\1{13}$/', $cnpj)) {
            return false;
        }

        $base = substr($cnpj, 0, 12);
        $digit1 = $this->calculateCheckDigit($base, [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]);
        $digit2 = $this->calculateCheckDigit($base . $digit1, [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]);

        return $cnpj === $base . $digit1 . $digit2;
    }

    private function validateCPF(string $cpf): bool
    {
        if (11 !== strlen($cpf) || preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }

        $base = substr($cpf, 0, 9);
        $digit1 = $this->calculateCheckDigit($base, [10, 9, 8, 7, 6, 5, 4, 3, 2]);
        $digit2 = $this->calculateCheckDigit($base . $digit1, [11, 10, 9, 8, 7, 6, 5, 4, 3, 2]);

        return $cpf === $base . $digit1 . $digit2;
    }

    /**
     * @param list<int> $weights
     */
    private function calculateCheckDigit(string $digits, array $weights): string
    {
        $sum = 0;
        $size = strlen($digits);

        for ($i = 0; $i < $size; $i++) {
            $sum += ((int) $digits[$i]) * $weights[$i];
        }

        $remainder = $sum % 11;
        $digit = $remainder < 2 ? 0 : 11 - $remainder;

        return (string) $digit;
    }
}

$plugin = new BrazilDocumentPlugin();
\YeAPF\Plugins\Registry::registerDocumentValidator($plugin);
\YeAPF\Plugins\Registry::registerTypeProvider($plugin);
