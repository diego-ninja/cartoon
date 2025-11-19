<?php

// ABOUTME: Thrown when TOON input violates spec in strict mode.
// ABOUTME: Examples: array length mismatch, inconsistent delimiters.

declare(strict_types=1);

namespace Ninja\Cartoon\Exception;

final class ValidationException extends ToonException
{
    public function __construct(
        string $message,
        private readonly ?int $lineNumber = null,
    ) {
        parent::__construct($message);
    }

    public function getLineNumber(): ?int
    {
        return $this->lineNumber;
    }
}
