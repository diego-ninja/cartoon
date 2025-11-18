<?php

// ABOUTME: Identifies the type of token during TOON parsing.
// ABOUTME: Used by tokenizer to categorize each line.

declare(strict_types=1);

namespace Toon\Decoder\Enum;

enum TokenType
{
    case ObjectKey;
    case ArrayHeader;
    case ListItem;
    case Primitive;
    case Empty;
    case BlankLine;
}
