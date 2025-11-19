<?php

// ABOUTME: Represents the result of validating TOON content.
// ABOUTME: Contains success status and error information if validation failed.

declare(strict_types=1);

namespace Ninja\Cartoon;

use Throwable;

final readonly class ValidationResult
{
    private function __construct(
        private bool $valid,
        private ?Throwable $error,
    ) {}

    public static function success(): self
    {
        return new self(true, null);
    }

    public static function failure(Throwable $error): self
    {
        return new self(false, $error);
    }

    public function isValid(): bool
    {
        return $this->valid;
    }

    public function getError(): ?Throwable
    {
        return $this->error;
    }
}
