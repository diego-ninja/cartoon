<?php

// ABOUTME: Thrown when circular reference detected during encoding.
// ABOUTME: Prevents infinite loops when encoding object graphs.

declare(strict_types=1);

namespace Toon\Exception;

final class CircularReferenceException extends ToonException {}
