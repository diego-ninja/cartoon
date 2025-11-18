<?php

declare(strict_types=1);

namespace Toon\Tests\Unit\Encoder;

use PHPUnit\Framework\TestCase;
use Toon\EncodeOptions;
use Toon\Encoder\Writer;
use Toon\Enum\DelimiterType;
use Toon\Enum\IndentationType;
use Toon\Exception\CircularReferenceException;
use Toon\Exception\UnencodableException;

final class WriterTest extends TestCase
{
    // -------------------------------------------------------------------------
    // SCALARS & BASICS
    // -------------------------------------------------------------------------

    public function test_write_primitives(): void
    {
        $writer = new Writer(new EncodeOptions());

        $this->assertSame('42', $writer->write(42));
        $this->assertSame('true', $writer->write(true));
        $this->assertSame('false', $writer->write(false));
        $this->assertSame('null', $writer->write(null));
    }

    public function test_write_floats_handling(): void
    {
        $writer = new Writer(new EncodeOptions());

        $this->assertSame('3.14', $writer->write(3.14));
        $this->assertSame('0', $writer->write(0.0));
        $this->assertSame('0', $writer->write(-0.0));

        // Test cleanup of trailing zeros
        $this->assertSame('10.5', $writer->write(10.500));

        // Test scientific notation conversion to decimal
        // 1.2e-5 becomes 0.000012
        $this->assertSame('0.000012', $writer->write(1.2e-5));
    }

    public function test_write_inf_and_nan_are_null(): void
    {
        $writer = new Writer(new EncodeOptions());

        // Per spec §3, NaN and ±Infinity MUST be normalized to null.
        $this->assertSame('null', $writer->write(INF));
        $this->assertSame('null', $writer->write(-INF));
        $this->assertSame('null', $writer->write(NAN));
    }

    // -------------------------------------------------------------------------
    // STRINGS & KEYS
    // -------------------------------------------------------------------------

    public function test_write_strings_quoting(): void
    {
        $writer = new Writer(new EncodeOptions());

        // Simple string
        $this->assertSame('hello', $writer->write('hello'));

        // Needs quoting
        $this->assertSame('""', $writer->write(''));
        $this->assertSame('"true"', $writer->write('true'));
        $this->assertSame('"null"', $writer->write('null'));
        $this->assertSame('"hello world"', $writer->write('hello world'));
        $this->assertSame('"key:val"', $writer->write('key:val'));
        $this->assertSame('"[bracket]"', $writer->write('[bracket]'));
    }

    public function test_write_numeric_like_string_is_quoted(): void
    {
        $writer = new Writer(new EncodeOptions());

        // These look like numbers, but are strings, so they MUST be quoted.
        $this->assertSame('"42"', $writer->write('42'));
        $this->assertSame('"-3.14"', $writer->write('-3.14'));
        $this->assertSame('"1e-6"', $writer->write('1e-6'));

        // This looks like an octal number in some languages, but it's a string.
        $this->assertSame('"07"', $writer->write('07'));
    }

    public function test_write_string_escaping(): void
    {
        $writer = new Writer(new EncodeOptions());

        $input = "Line 1\nLine 2\t\"Quoted\"";
        // Expect: "Line 1\nLine 2\t\"Quoted\"" (wrapped in quotes)
        $expected = '"Line 1\\nLine 2\\t\\"Quoted\\""';

        $this->assertSame($expected, $writer->write($input));
    }

    public function test_write_string_control_chars_throws(): void
    {
        $writer = new Writer(new EncodeOptions());
        // ASCII 0x00 (Null byte) should throw
        $this->expectException(UnencodableException::class);
        $writer->write("Bad\0Char");
    }

    public function test_encode_complex_keys(): void
    {
        $writer = new Writer(new EncodeOptions());

        $data = [
            'simple' => 1,
            'complex key' => 2,
            'key:with:colons' => 3,
            '' => 4,
        ];

        $expected = <<<TOON
simple: 1
"complex key": 2
"key:with:colons": 3
"": 4
TOON;
        $this->assertSame($expected, $writer->write($data));
    }

    // -------------------------------------------------------------------------
    // LISTS: COMPACT STRATEGY
    // -------------------------------------------------------------------------

    public function test_list_compact_format(): void
    {
        $writer = new Writer(new EncodeOptions(maxCompactArrayLength: 5));

        // Root list
        $this->assertSame('[3]: 1,2,3', $writer->write([1, 2, 3]));

        // Nested list
        $data = ['ids' => [1, 2, 3]];
        $this->assertSame('ids[3]: 1,2,3', $writer->write($data));
    }

    public function test_list_compact_falls_back_on_length(): void
    {
        // Max length 2, but list has 3 items -> triggers Expanded format (since primitives aren't a table)
        $writer = new Writer(new EncodeOptions(maxCompactArrayLength: 2));

        $result = $writer->write(['ids' => [1, 2, 3]]);

        // Falls back to expanded list
        $expected = <<<TOON
ids[3]:
  - 1
  - 2
  - 3
TOON;
        $this->assertSame($expected, $result);
    }

    public function test_list_compact_empty(): void
    {
        $writer = new Writer(new EncodeOptions());
        $this->assertSame('[0]:', $writer->write([]));
        $this->assertSame('items[0]:', $writer->write(['items' => []]));
    }

    // -------------------------------------------------------------------------
    // LISTS: TABLE STRATEGY
    // -------------------------------------------------------------------------

    public function test_list_table_format(): void
    {
        $writer = new Writer(new EncodeOptions());

        // Homogeneous objects
        $data = [
            'users' => [
                ['id' => 1, 'name' => 'Alice'],
                ['id' => 2, 'name' => 'Bob'],
            ],
        ];

        // Expect header with braces {id,name} and rows WITHOUT braces
        $expected = <<<TOON
users[2]{id,name}:
  1,Alice
  2,Bob
TOON;

        $this->assertSame($expected, $writer->write($data));
    }

    public function test_list_table_format_values_quoting(): void
    {
        $writer = new Writer(new EncodeOptions());

        $data = [
            'items' => [
                ['col' => 'simple'],
                ['col' => 'complex,value'], // contains comma, needs quotes
            ],
        ];

        $expected = <<<TOON
items[2]{col}:
  simple
  "complex,value"
TOON;

        $this->assertSame($expected, $writer->write($data));
    }

    public function test_list_table_fallback_on_mixed_keys(): void
    {
        $writer = new Writer(new EncodeOptions());

        // Keys don't match -> Expanded format
        $data = [
            'mix' => [
                ['a' => 1],
                ['b' => 2],
            ],
        ];

        $expected = <<<TOON
mix[2]:
  - a: 1
  - b: 2
TOON;
        $this->assertSame($expected, $writer->write($data));
    }

    public function test_list_table_fallback_on_nested_values(): void
    {
        $writer = new Writer(new EncodeOptions());

        // Value is array -> Not primitive -> Expanded format
        $data = [
            'deep' => [
                ['tags' => [1, 2]],
                ['tags' => [3, 4]],
            ],
        ];

        $expected = <<<TOON
deep[2]:
  - tags[2]: 1,2
  - tags[2]: 3,4
TOON;
        $this->assertSame($expected, $writer->write($data));
    }

    public function test_list_table_fallback_on_non_array_item(): void
    {
        $writer = new Writer(new EncodeOptions());

        // Contains a non-array item, so should not be a table
        $data = [
            'items' => [
                ['id' => 1],
                'not-an-array',
            ],
        ];

        $expected = <<<TOON
items[2]:
  - id: 1
  - "not-an-array"
TOON;
        $this->assertSame($expected, $writer->write($data));
    }

    public function test_list_table_fallback_on_list_items(): void
    {
        $writer = new Writer(new EncodeOptions());

        // A list of lists should not be a table.
        $data = [
            'items' => [
                [1, 2],
                [3, 4],
            ],
        ];

        $expected = <<<TOON
items[2]:
  - "[2]: 1,2"
  - "[2]: 3,4"
TOON;
        $this->assertSame($expected, $writer->write($data));
    }

    public function test_write_empty_object_as_list_item(): void
    {
        $writer = new Writer(new EncodeOptions());

        // Per spec §10, an empty object as a list item is just a hyphen.
        $data = ['items' => [new \stdClass()]];

        $expected = <<<TOON
items[1]:
  -
TOON;
        $this->assertSame($expected, $writer->write($data));
    }

    // -------------------------------------------------------------------------
    // LISTS: EXPANDED STRATEGY (COMPLEX)
    // -------------------------------------------------------------------------

    public function test_list_expanded_complex_alignment(): void
    {
        $writer = new Writer(new EncodeOptions());

        $data = [
            'products' => [
                [
                    'id' => 1,
                    'meta' => [
                        'active' => true,
                    ],
                ],
                [
                    'id' => 2,
                    'name' => 'Phone',
                ],
            ],
        ];

        // Check strict indentation alignment
        $expected = <<<TOON
products[2]:
  - id: 1
    meta:
      active: true
  - id: 2
    name: Phone
TOON;
        $this->assertSame($expected, $writer->write($data));
    }

    // -------------------------------------------------------------------------
    // OBJECTS & ENUMS
    // -------------------------------------------------------------------------

    public function test_write_enums(): void
    {
        $writer = new Writer(new EncodeOptions());

        $data = [
            'status' => TestUnitEnum::Pending,
            'color' => TestBackedEnum::Red,
            'number' => TestIntEnum::One,
        ];

        $expected = <<<TOON
status: Pending
color: red
number: 1
TOON;
        $this->assertSame($expected, $writer->write($data));
    }

    public function test_write_closure_throws(): void
    {
        $writer = new Writer(new EncodeOptions());
        $closure = fn() => true;

        $this->expectException(UnencodableException::class);
        $this->expectExceptionMessage('Cannot encode closure');
        $writer->write(['fn' => $closure]);
    }

    public function test_write_resource_throws(): void
    {
        $writer = new Writer(new EncodeOptions());
        $res = fopen('php://memory', 'r');

        try {
            $this->expectException(UnencodableException::class);
            $this->expectExceptionMessage('Cannot encode resource');
            $writer->write(['file' => $res]);
        } finally {
            if (is_resource($res)) {
                fclose($res);
            }
        }
    }

    public function test_circular_reference_throws(): void
    {
        $writer = new Writer(new EncodeOptions());

        $a = new \stdClass();
        $b = new \stdClass();

        $a->child = $b;
        $b->parent = $a; // Cycle

        $this->expectException(CircularReferenceException::class);
        $writer->write($a);
    }

    public function test_write_empty_root_object_is_empty_string(): void
    {
        $writer = new Writer(new EncodeOptions());

        // Per spec §8, an empty object at the root yields an empty document.
        $this->assertSame('', $writer->write(new \stdClass()));
        $this->assertSame('', $writer->write((object)[]));
    }

    // -------------------------------------------------------------------------
    // OPTIONS
    // -------------------------------------------------------------------------

    public function test_indentation_options_tabs(): void
    {
        $options = new EncodeOptions(
            indentationType: IndentationType::Tabs,
        );
        $writer = new Writer($options);

        $data = [
            'level1' => [
                'level2' => 'val',
            ],
        ];

        // \t instead of spaces
        $expected = "level1:\n\tlevel2: val";
        $this->assertSame($expected, $writer->write($data));
    }

    public function test_custom_delimiter(): void
    {
        // Assuming EncodeOptions supports preferredDelimiter
        $options = new EncodeOptions(
            preferredDelimiter: DelimiterType::Pipe,
        );
        $writer = new Writer($options);

        $this->assertSame('[3]: 1|2|3', $writer->write([1, 2, 3]));
    }
}

// -------------------------------------------------------------------------
// HELPERS FOR TESTS
// -------------------------------------------------------------------------

enum TestUnitEnum
{
    case Pending;
    case Done;
}

enum TestBackedEnum: string
{
    case Red = 'red';
    case Blue = 'blue';
}

enum TestIntEnum: int
{
    case One = 1;
    case Two = 2;
}
