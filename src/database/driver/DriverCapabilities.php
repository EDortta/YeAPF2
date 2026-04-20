<?php declare(strict_types=1);

namespace YeAPF\Connection\DB\Driver;

final class DriverCapabilities
{
    /** @var array<string,bool> */
    private array $flags;

    /**
     * @param array<string,bool> $flags
     */
    public function __construct(array $flags = [])
    {
        $this->flags = [
            'transactions' => $flags['transactions'] ?? true,
            'prepared_statements' => $flags['prepared_statements'] ?? true,
            'schema_inspection' => $flags['schema_inspection'] ?? false,
            'ddl_synthesis' => $flags['ddl_synthesis'] ?? false,
            'json_type' => $flags['json_type'] ?? false,
            'enum_type' => $flags['enum_type'] ?? false,
            'upsert' => $flags['upsert'] ?? false,
            'returning_clause' => $flags['returning_clause'] ?? false,
        ];
    }

    public function isEnabled(string $capability): bool
    {
        return $this->flags[$capability] ?? false;
    }

    /**
     * @return array<string,bool>
     */
    public function toArray(): array
    {
        return $this->flags;
    }

    /**
     * @param array<string,mixed> $flags
     */
    public static function fromArray(array $flags): self
    {
        $normalized = [];
        foreach ($flags as $name => $enabled) {
            if (!is_string($name)) {
                continue;
            }

            $normalized[$name] = (bool) $enabled;
        }

        return new self($normalized);
    }
}
