<?php

// ABOUTME: Thrown when invalid escape sequence is encountered.
// ABOUTME: Only five escapes are valid: \\, \", \n, \r, \t

declare(strict_types=1);

namespace Toon\Exception;

final class EscapeException extends ToonException {}
