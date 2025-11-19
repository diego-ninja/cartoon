<?php

// ABOUTME: Thrown when PHP value cannot be encoded to TOON.
// ABOUTME: Examples: resources, closures, INF, NAN, multi-line strings.

declare(strict_types=1);

namespace Ninja\Cartoon\Exception;

final class UnencodableException extends ToonException
{
    public function __construct(
        string $message,
        private readonly ?string $path = null,
    ) {
        parent::__construct($message);
    }

    public function getPath(): ?string
    {
        return $this->path;
    }
}
