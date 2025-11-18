<?php

declare(strict_types=1);

namespace Toon\Tests\Unit\Encoder;

use PHPUnit\Framework\TestCase;
use Toon\EncodeOptions;
use Toon\Encoder\Writer;
use Toon\Exception\CircularReferenceException;
use Toon\Exception\UnencodableException;

final class WriterTest extends TestCase
{
    public function test_write_simple_object(): void
    {
        $writer = new Writer(new EncodeOptions());
        $result = $writer->write(['name' => 'Alice', 'age' => 30]);

        $expected = <<<TOON
name: Alice
age: 30
TOON;

        $this->assertSame($expected, $result);
    }

    public function test_write_nested_object(): void
    {
        $writer = new Writer(new EncodeOptions());
        $data = [
            'user' => [
                'name' => 'Bob',
                'age' => 25,
            ],
        ];

        $result = $writer->write($data);

        $expected = <<<TOON
user:
  name: Bob
  age: 25
TOON;

        $this->assertSame($expected, $result);
    }

    public function test_write_simple_array(): void
    {
        $writer = new Writer(new EncodeOptions());
        $result = $writer->write(['a', 'b', 'c']);

        $expected = "[3]: a,b,c";

        $this->assertSame($expected, $result);
    }

    public function test_write_primitive(): void
    {
        $writer = new Writer(new EncodeOptions());

        $this->assertSame('42', $writer->write(42));
        $this->assertSame('3.14', $writer->write(3.14));
        $this->assertSame('true', $writer->write(true));
        $this->assertSame('false', $writer->write(false));
        $this->assertSame('null', $writer->write(null));
    }

    public function test_write_resource_throws(): void
    {
        $writer = new Writer(new EncodeOptions());
        $resource = fopen('php://memory', 'r');

        $this->expectException(UnencodableException::class);
        $writer->write($resource);

        if (is_resource($resource)) {
            fclose($resource);
        }
    }

    public function test_write_circular_reference_throws(): void
    {
        $writer = new Writer(new EncodeOptions());

        $obj = new \stdClass();
        $obj->self = $obj;

        $this->expectException(CircularReferenceException::class);
        $writer->write($obj);
    }
}
