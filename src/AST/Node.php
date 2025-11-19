<?php

// ABOUTME: Base interface for all AST nodes in the TOON parser.
// ABOUTME: Provides common methods for type identification and PHP conversion.

declare(strict_types=1);

namespace Ninja\Cartoon\AST;

interface Node
{
    public function getType(): NodeType;

    public function toPhp(): mixed;
}
