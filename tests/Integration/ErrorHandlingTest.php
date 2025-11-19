<?php

// ABOUTME: Tests error handling and exception cases for TOON encoding/decoding.
// ABOUTME: Ensures proper exceptions are thrown for invalid inputs and circular references.

declare(strict_types=1);

namespace Ninja\Cartoon\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Ninja\Cartoon\Exception\CircularReferenceException;
use Ninja\Cartoon\Exception\EscapeException;
use Ninja\Cartoon\Exception\UnencodableException;
use Ninja\Cartoon\Toon;

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
        $this->expectException(UnencodableException::class);
        Toon::encode(fn() => 'test');
    }

    public function test_encode_inf_and_nan_are_null(): void
    {
        // Per spec, INF and NAN must be encoded as null
        $this->assertSame('null', Toon::encode(INF));
        $this->assertSame('null', Toon::encode(NAN));
    }

    public function test_encode_circular_reference_throws(): void
    {
        $this->expectException(CircularReferenceException::class);

        $a = new \stdClass();
        $b = new \stdClass();
        $a->child = $b;
        $b->parent = $a;

        Toon::encode($a);
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
