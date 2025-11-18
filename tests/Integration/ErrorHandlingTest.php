<?php

// ABOUTME: Tests error handling and exception cases for TOON encoding/decoding.
// ABOUTME: Ensures proper exceptions are thrown for invalid inputs and circular references.

declare(strict_types=1);

namespace Toon\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Toon\Exception\CircularReferenceException;
use Toon\Exception\EscapeException;
use Toon\Exception\UnencodableException;
use Toon\Toon;

final class ErrorHandlingTest extends TestCase
{
    public function test_encode_resource_throws(): void
    {
        $resource = fopen('php://memory', 'r');

        try {
            $this->expectException(UnencodableException::class);
            Toon::encode(['resource' => $resource]);
        } finally {
            if (is_resource($resource)) {
                fclose($resource);
            }
        }
    }

    public function test_encode_closure_throws(): void
    {
        $closure = fn() => 'test';

        $this->expectException(UnencodableException::class);
        Toon::encode(['closure' => $closure]);
    }

    public function test_encode_inf_throws(): void
    {
        $this->expectException(UnencodableException::class);
        Toon::encode(['value' => INF]);
    }

    public function test_encode_nan_throws(): void
    {
        $this->expectException(UnencodableException::class);
        Toon::encode(['value' => NAN]);
    }

    public function test_encode_circular_reference_throws(): void
    {
        $obj = new \stdClass();
        $obj->self = $obj;

        $this->expectException(CircularReferenceException::class);
        Toon::encode($obj);
    }

    public function test_decode_invalid_escape_throws(): void
    {
        $toon = 'text: "invalid\\xescape"';

        $this->expectException(EscapeException::class);
        Toon::decode($toon);
    }

    public function test_decode_incomplete_escape_throws(): void
    {
        $toon = 'text: "ends with backslash\\"';

        $this->expectException(EscapeException::class);
        Toon::decode($toon);
    }
}
