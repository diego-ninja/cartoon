<?php

// ABOUTME: Tests edge cases in Writer to increase coverage.
// ABOUTME: Covers special numeric values, objects, and complex arrays.

declare(strict_types=1);

namespace Toon\Tests\Unit\Encoder;

use PHPUnit\Framework\TestCase;
use Toon\EncodeOptions;
use Toon\Encoder\Writer;
use Toon\Exception\CircularReferenceException;
use Toon\Exception\UnencodableException;

final class WriterEdgeCasesTest extends TestCase
{
    public function test_encode_stdclass_object(): void
    {
        $obj = new \stdClass();
        $obj->name = 'Alice';
        $obj->age = 30;

        $writer = new Writer(new EncodeOptions());
        $result = $writer->write($obj);

        $this->assertStringContainsString('name: Alice', $result);
        $this->assertStringContainsString('age: 30', $result);
    }

    public function test_encode_float_with_decimals(): void
    {
        $data = ['pi' => 3.14159, 'ratio' => 0.5];

        $writer = new Writer(new EncodeOptions());
        $result = $writer->write($data);

        $this->assertStringContainsString('pi: 3.14159', $result);
        $this->assertStringContainsString('ratio: 0.5', $result);
    }

    public function test_encode_integer_keys(): void
    {
        $data = [0 => 'zero', 1 => 'one', 2 => 'two'];

        $writer = new Writer(new EncodeOptions());
        $result = $writer->write($data);

        $this->assertStringContainsString('[3]:', $result);
    }

    public function test_encode_non_sequential_integer_keys(): void
    {
        $data = [0 => 'a', 2 => 'b', 5 => 'c'];

        $writer = new Writer(new EncodeOptions());
        $result = $writer->write($data);

        // Non-sequential keys should be treated as object with quoted keys
        $this->assertStringContainsString('"0": a', $result);
        $this->assertStringContainsString('"2": b', $result);
        $this->assertStringContainsString('"5": c', $result);
    }

    public function test_encode_object_with_circular_reference(): void
    {
        $obj1 = new \stdClass();
        $obj2 = new \stdClass();
        $obj1->ref = $obj2;
        $obj2->ref = $obj1; // Circular!

        $writer = new Writer(new EncodeOptions());

        $this->expectException(CircularReferenceException::class);
        $writer->write($obj1);
    }

    public function test_encode_resource_throws_exception(): void
    {
        $resource = fopen('php://memory', 'r');
        if ($resource === false) {
            $this->fail('Failed to open resource');
        }

        try {
            $writer = new Writer(new EncodeOptions());

            $this->expectException(UnencodableException::class);
            $writer->write(['resource' => $resource]);
        } finally {
            fclose($resource);
        }
    }

    public function test_encode_nested_stdclass_objects(): void
    {
        $inner = new \stdClass();
        $inner->value = 42;

        $outer = new \stdClass();
        $outer->nested = $inner;
        $outer->name = 'test';

        $writer = new Writer(new EncodeOptions());
        $result = $writer->write($outer);

        $this->assertStringContainsString('name: test', $result);
        $this->assertStringContainsString('nested:', $result);
        $this->assertStringContainsString('value: 42', $result);
    }

    public function test_encode_array_of_stdclass(): void
    {
        $obj1 = new \stdClass();
        $obj1->id = 1;

        $obj2 = new \stdClass();
        $obj2->id = 2;

        $data = ['items' => [$obj1, $obj2]];

        $writer = new Writer(new EncodeOptions());
        $result = $writer->write($data);

        $this->assertStringContainsString('items[2]:', $result);
        $this->assertStringContainsString('id: 1', $result);
        $this->assertStringContainsString('id: 2', $result);
    }

    public function test_encode_mixed_array_with_objects_and_primitives(): void
    {
        $obj = new \stdClass();
        $obj->type = 'object';

        $data = [
            'items' => [
                'simple',
                $obj,
                123,
            ],
        ];

        $writer = new Writer(new EncodeOptions());
        $result = $writer->write($data);

        $this->assertStringContainsString('items[3]:', $result);
    }
}
