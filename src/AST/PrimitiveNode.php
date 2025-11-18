<?php

// ABOUTME: Represents a primitive value in the AST.
// ABOUTME: Handles strings, numbers, booleans, and null.

declare(strict_types=1);

namespace Toon\AST;

final readonly class PrimitiveNode implements Node
{
    public function __construct(
        private string|int|float|bool|null $value,
    ) {
    }

    public function getType(): NodeType
    {
        return NodeType::Primitive;
    }

    public function toPhp(): string|int|float|bool|null
    {
        return $this->value;
    }
}
