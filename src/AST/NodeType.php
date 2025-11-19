<?php

// ABOUTME: Identifies the type of an AST node.
// ABOUTME: Used for type checking and polymorphic behavior.

declare(strict_types=1);

namespace Ninja\Cartoon\AST;

enum NodeType
{
    case Object;
    case Array;
    case Primitive;
}
