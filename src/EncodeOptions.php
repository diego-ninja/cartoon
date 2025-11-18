<?php

// ABOUTME: Configuration options for encoding PHP values to TOON.
// ABOUTME: Controls formatting, delimiters, and array representation.

declare(strict_types=1);

namespace Toon;

final readonly class EncodeOptions
{
    public function __construct(
        public DelimiterType $preferredDelimiter = DelimiterType::Comma,
        public int $indentSize = 2,
        public IndentationType $indentationType = IndentationType::Spaces,
        public bool $prettyArrays = true,
        public int $maxCompactArrayLength = 10,
    ) {
    }
}
