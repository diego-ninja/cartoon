<?php

// ABOUTME: Represents a single token from TOON input.
// ABOUTME: Contains type, value, indentation level, and source line number.

declare(strict_types=1);

namespace Toon\Decoder;

use Toon\Decoder\Enum\TokenType;

final readonly class Token
{
    public function __construct(
        public TokenType $type,
        public string $value,
        public int $indentLevel,
        public int $lineNumber,
    ) {}
}
