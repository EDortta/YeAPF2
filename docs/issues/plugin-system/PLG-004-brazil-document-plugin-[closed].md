# PLG-004 — BrazilDocumentPlugin (CNPJ + CPF)

## Files to create
- `src/plugins/br-document-plugin.php`

## Problem
CNPJ and CPF authenticity validation does not exist in YeAPF2. The type definitions in `yTypes.php` carry only a permissive regex (`/^[^\p{C}]*$/`) with no digit-count check and no algorithmic validation. Brazil is the primary development locale and the most-used document type pair.

## What to do

Create `BrazilDocumentPlugin` implementing both `DocumentValidatorInterface` and `TypeProviderInterface`:

### Validator — CNPJ algorithm
Standard Brazilian CNPJ validation:
- Strip non-digits (value arrives already stripped by sedInput, but defensive)
- Reject if length ≠ 14 or all digits the same
- Compute two check digits using weights `[5,4,3,2,9,8,7,6,5,4,3,2]` and `[6,5,4,3,2,9,8,7,6,5,4,3,2]`
- Modulo 11 rule (remainder < 2 → digit = 0, else digit = 11 − remainder)
- Compare with digits 13 and 14

### Validator — CPF algorithm
Standard Brazilian CPF validation:
- Strip non-digits; length must be 11; reject all-same
- Two check digits: weights `[10..2]` then `[11..2]`; same modulo 11 rule

### Type definitions (`getTypeDefinitions()`)
```php
return [
    'CNPJ' => [
        'type'                => YeAPF_TYPE_STRING,
        'length'              => 14,
        'acceptNULL'          => false,
        'regExpression'       => '/^\d{14}$/',
        'sedInputExpression'  => '/[^0-9]//',
        'sedOutputExpression' => '/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/$1.$2.$3\/$4-$5/',
        'authenticityChecker' => 'BR.CNPJ',
        'unique'              => false,
        'required'            => false,
        'primary'             => false,
        'tag'                 => ';;',
    ],
    'CPF' => [
        'type'                => YeAPF_TYPE_STRING,
        'length'              => 11,
        'acceptNULL'          => false,
        'regExpression'       => '/^\d{11}$/',
        'sedInputExpression'  => '/[^0-9]//',
        'sedOutputExpression' => '/(\d{3})(\d{3})(\d{3})(\d{2})/$1.$2.$3-$4/',
        'authenticityChecker' => 'BR.CPF',
        'unique'              => false,
        'required'            => false,
        'primary'             => false,
        'tag'                 => ';;',
    ],
];
```

### Self-registration at boot
The plugin file ends with:
```php
$plugin = new BrazilDocumentPlugin();
\YeAPF\Plugins\Registry::registerDocumentValidator($plugin);
\YeAPF\Plugins\Registry::registerTypeProvider($plugin);
```
No global variable. No `ServicePlugin` base class required (pure interface implementation).

## Acceptance criteria
- Known-valid CNPJ list passes `validate('BR.CNPJ', $value)`
- Known-invalid CNPJ list (wrong check digit, all-zeros, all-ones) returns false
- Same for CPF
- `BasicTypes::get('CNPJ')['authenticityChecker']` === `'BR.CNPJ'` after boot with plugin loaded
- Conformance test (`PLG-006` base class) passes for this plugin
- No `$debug`, no `var_dump`, no `print_r` in production path

## Notes
- The existing `CNPJ`/`CPF` entries in `yTypes.php` are **overwritten** by this plugin via `BasicTypes::set()`. If the plugin is not loaded, the bare-bones type still exists (just without digit verification). This is the intended graceful degradation.
- `src/regexp/BR.php` constants (`YeAPF_SED_BR_IN_CNPJ` etc.) remain for backward compatibility but are no longer the canonical source — the plugin definition is.
