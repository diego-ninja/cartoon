<?php

// ABOUTME: Configuration options for decoding TOON to PHP values.
// ABOUTME: Controls strict mode validation and key order preservation.

declare(strict_types=1);

namespace Ninja\Cartoon;

final readonly class DecodeOptions
{
    public function __construct(
        public bool $strict = true,
        public bool $preserveKeyOrder = true,
        public int $indentSize = 2,
    ) {}

    public static function default(): self
    {
        return new self();
    }
}
