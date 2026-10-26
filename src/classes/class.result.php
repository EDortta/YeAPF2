<?php

declare(strict_types=1);

namespace YeAPF;

class Result extends KeyData implements \JsonSerializable
{
    public function __construct(int $ret_code = 200)
    {
        parent::__construct();
        $this['return'] = $ret_code;
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return $this->redact($this->exportData());
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function redact(array $data): array
    {
        foreach ($data as $key => $value) {
            if (preg_match('/(?:password|passwd|secret|token|authorization)/i', (string) $key) === 1) {
                $data[$key] = '[REDACTED]';
                continue;
            }
            if (preg_match('/(?:file|path)/i', (string) $key) === 1 && is_string($value)) {
                $data[$key] = basename($value);
                continue;
            }
            if (is_array($value)) {
                $data[$key] = $this->redact($value);
            }
        }
        return $data;
    }
}
