<?php

// ABOUTME: Thrown when TOON input contains syntax errors.
// ABOUTME: Includes context like line number, column, and code snippet.

declare(strict_types=1);

namespace Toon\Exception;

final class SyntaxException extends ToonException
{
    public function __construct(
        string $message,
        private readonly ?int $lineNumber = null,
        private readonly ?int $column = null,
        private readonly ?string $snippet = null,
    ) {
        parent::__construct($message);
    }

    public function getLineNumber(): ?int
    {
        return $this->lineNumber;
    }

    public function getColumn(): ?int
    {
        return $this->column;
    }

    public function getSnippet(): ?string
    {
        return $this->snippet;
    }
}
