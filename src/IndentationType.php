<?php

// ABOUTME: Specifies whether to use spaces or tabs for indentation.
// ABOUTME: Used in encoder options for output formatting.

declare(strict_types=1);

namespace Toon;

enum IndentationType
{
    case Spaces;
    case Tabs;
}
