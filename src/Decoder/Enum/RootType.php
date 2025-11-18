<?php

// ABOUTME: Determines the type of the root document.
// ABOUTME: Detected from the first non-empty line during parsing.

declare(strict_types=1);

namespace Toon\Decoder\Enum;

enum RootType
{
    case Object;
    case Array;
    case Primitive;
}
