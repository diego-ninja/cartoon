<?php

// ABOUTME: Tests edge cases and boundary conditions for TOON encoding/decoding.
// ABOUTME: Ensures proper handling of empty values, special characters, and deeply nested structures.

declare(strict_types=1);

namespace Toon\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Toon\Toon;

final class EdgeCasesTest extends TestCase
{
    public function test_empty_object(): void
    {
        // Empty document decodes to empty array
        $decoded = Toon::decode('');
        $this->assertSame([], $decoded);
    }

    public function test_empty_array(): void
    {
        $encoded = Toon::encode([]);
        $this->assertSame('[0]:', $encoded);

        $decoded = Toon::decode($encoded);
        $this->assertSame([], $decoded);
    }

    public function test_empty_string_value(): void
    {
        $data = ['name' => ''];
        $encoded = Toon::encode($data);
        $decoded = Toon::decode($encoded);

        $this->assertSame($data, $decoded);
    }

    public function test_string_with_whitespace(): void
    {
        $data = ['text' => 'hello world'];
        $encoded = Toon::encode($data);
        $decoded = Toon::decode($encoded);

        $this->assertSame($data, $decoded);
    }

    public function test_string_with_special_chars(): void
    {
        $data = ['text' => 'hello "world"'];
        $encoded = Toon::encode($data);
        $decoded = Toon::decode($encoded);

        $this->assertSame($data, $decoded);
    }

    public function test_canonical_numbers(): void
    {
        $data = [
            'zero' => 0,
            'int' => 42,
            'negative' => -17,
            'float' => 3.14,
            'small' => 0.5,
        ];

        $encoded = Toon::encode($data);
        $decoded = Toon::decode($encoded);

        $this->assertSame($data, $decoded);

        // Verify no exponential notation in numbers
        $this->assertDoesNotMatchRegularExpression('/\d[eE][+-]?\d/', $encoded);
    }

    public function test_boolean_values(): void
    {
        $data = [
            'yes' => true,
            'no' => false,
        ];

        $encoded = Toon::encode($data);
        $decoded = Toon::decode($encoded);

        $this->assertSame($data, $decoded);
    }

    public function test_null_value(): void
    {
        $data = ['value' => null];
        $encoded = Toon::encode($data);
        $decoded = Toon::decode($encoded);

        $this->assertSame($data, $decoded);
    }

    public function test_deeply_nested_structure(): void
    {
        $data = [
            'level1' => [
                'level2' => [
                    'level3' => [
                        'value' => 'deep',
                    ],
                ],
            ],
        ];

        $encoded = Toon::encode($data);
        $decoded = Toon::decode($encoded);

        $this->assertSame($data, $decoded);
    }
}
