<?php declare(strict_types=1);

namespace YeAPF\SchemaBootstrap;

final class SchemaBootstrappedException extends \RuntimeException
{
    public function __construct(
        string $message,
        private readonly int $httpStatus = 503,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function getHttpStatus(): int
    {
        return $this->httpStatus;
    }
}
