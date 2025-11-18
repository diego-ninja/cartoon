<?php

// ABOUTME: Configuration options for encoding PHP values to TOON.
// ABOUTME: Controls formatting, delimiters, and array representation.

declare(strict_types=1);

namespace Toon;

use Toon\Enum\DelimiterType;
use Toon\Enum\IndentationType;

final readonly class EncodeOptions
{
    public function __construct(
        public DelimiterType $preferredDelimiter = DelimiterType::Comma,
        public int $indentSize = 2,
        public IndentationType $indentationType = IndentationType::Spaces,
        public int $maxCompactArrayLength = 10,
    ) {}

    public static function default(): EncodeOptions
    {
        return new self();
    }
}
