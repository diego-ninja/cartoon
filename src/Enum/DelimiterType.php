<?php

// ABOUTME: Defines the three delimiter types for TOON arrays.
// ABOUTME: Comma (default), Tab, or Pipe as specified in array headers.

declare(strict_types=1);

namespace Toon\Enum;

enum DelimiterType: string
{
    case Comma = ',';
    case Tab = "\t";
    case Pipe = '|';
}
