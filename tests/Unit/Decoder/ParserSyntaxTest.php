<?php

declare(strict_types=1);

namespace Ninja\Cartoon\Tests\Unit\Decoder;

use PHPUnit\Framework\TestCase;
use Ninja\Cartoon\AST\ObjectNode;
use Ninja\Cartoon\DecodeOptions;
use Ninja\Cartoon\Decoder\Parser;
use Ninja\Cartoon\Decoder\Token;
use Ninja\Cartoon\Decoder\Enum\TokenType;
use Ninja\Cartoon\Exception\SyntaxException;

final class ParserSyntaxTest extends TestCase
{
    public function test_invalid_object_key_throws_exception(): void
    {
        // This simulates a token stream where a key is 'null' instead of a string
        $tokens = [
            new Token(TokenType::ObjectKey, 'null', 0, 1),
            new Token(TokenType::Primitive, 'some_value', 0, 1),
        ];

        $parser = new Parser(new DecodeOptions());

        $this->expectException(SyntaxException::class);
        $this->expectExceptionMessage('Object key must be string, got NULL');

        // The ValueParser will turn the string 'null' into a real null,
        // which the Parser should reject as a key.
        $parser->parse($tokens);
    }

    public function test_parsing_stops_on_dedent(): void
    {
        $tokens = [
            new Token(TokenType::ObjectKey, 'level1', 0, 1),
            new Token(TokenType::ObjectKey, 'level2', 2, 2), // Indent
            new Token(TokenType::Primitive, 'value', 2, 2),
            new Token(TokenType::ObjectKey, 'another_level1', 0, 3), // Dedent
            new Token(TokenType::Primitive, 'another_value', 0, 3),
        ];

        $parser = new Parser(new DecodeOptions());
        $node = $parser->parse($tokens);

        $this->assertInstanceOf(ObjectNode::class, $node);
        $result = $node->toPhp();

        $expected = [
            'level1' => [
                'level2' => 'value',
            ],
            'another_level1' => 'another_value',
        ];

        $this->assertSame($expected, $result);
    }

    public function test_expanded_list_of_objects(): void
    {
        $tokens = [
            new Token(TokenType::ArrayHeader, '[2]:', 0, 1),
            new Token(TokenType::ListItem, '-', 2, 2),
            new Token(TokenType::ObjectKey, 'id', 4, 2),
            new Token(TokenType::Primitive, '1', 4, 2),
            new Token(TokenType::ListItem, '-', 2, 3),
            new Token(TokenType::ObjectKey, 'id', 4, 3),
            new Token(TokenType::Primitive, '2', 4, 3),
        ];

        $parser = new Parser(new DecodeOptions());
        $node = $parser->parse($tokens);

        $expected = [
            ['id' => 1],
            ['id' => 2],
        ];

        $this->assertSame($expected, $node->toPhp());
    }

    public function test_array_with_pipe_delimiter(): void
    {
        // The tokenizer would produce a single token for a compact list.
        // The parser's job is to respect the delimiter from the header.
        $tokens = [
            new Token(TokenType::ArrayHeader, '[3|]:', 0, 1), // Using '|'
            new Token(TokenType::Primitive, 'a', 0, 1),
            new Token(TokenType::Primitive, 'b', 0, 1),
            new Token(TokenType::Primitive, 'c', 0, 1),
        ];

        $parser = new Parser(new DecodeOptions());
        $node = $parser->parse($tokens);
        $this->assertSame(['a', 'b', 'c'], $node->toPhp());
    }

    public function test_array_with_tab_delimiter(): void
    {
        $tokens = [
            new Token(TokenType::ArrayHeader, "[2\t]:", 0, 1), // Using tab
            new Token(TokenType::Primitive, 'a', 0, 1),
            new Token(TokenType::Primitive, 'b', 0, 1),
        ];

        $parser = new Parser(new DecodeOptions());
        $node = $parser->parse($tokens);
        $this->assertSame(['a', 'b'], $node->toPhp());
    }

    public function test_unattached_primitive_in_object_is_ignored(): void
    {
        $tokens = [
            new Token(TokenType::ObjectKey, 'key', 0, 1),
            new Token(TokenType::Primitive, 'value', 0, 1),
            new Token(TokenType::Primitive, 'garbage', 0, 2), // Not a key, not a value for a key
            new Token(TokenType::ObjectKey, 'another', 0, 3),
            new Token(TokenType::Primitive, 'another_value', 0, 3),
        ];

        $parser = new Parser(new DecodeOptions());
        $node = $parser->parse($tokens);

        $expected = [
            'key' => 'value',
            'another' => 'another_value',
        ];

        $this->assertSame($expected, $node->toPhp());
    }

    public function test_blank_line_in_array_throws_in_strict_mode(): void
    {
        $tokens = [
            new Token(TokenType::ArrayHeader, '[2]:', 0, 1),
            new Token(TokenType::ListItem, '-', 1, 2),
            new Token(TokenType::BlankLine, '', 0, 3), // Explicit blank line token
            new Token(TokenType::ListItem, '-', 1, 4),
        ];

        $parser = new Parser(new DecodeOptions(strict: true));

        $this->expectException(SyntaxException::class);
        $this->expectExceptionMessage('Blank lines are not allowed inside arrays in strict mode');

        $parser->parse($tokens);
    }

    public function test_tabular_row_width_mismatch_throws_in_strict_mode(): void
    {
        $tokens = [
            new Token(TokenType::ArrayHeader, 'items[2]{id,name}:', 0, 1), // Declares 2 fields
            new Token(TokenType::Primitive, '1,Alice', 1, 2), // Correct: 2 values
            new Token(TokenType::Primitive, '2', 1, 3), // Incorrect: 1 value
        ];

        $parser = new Parser(new DecodeOptions(strict: true));

        $this->expectException(SyntaxException::class);
        $this->expectExceptionMessage('Tabular row width mismatch: declared 2 fields, found 1 value(s)');

        $parser->parse($tokens);
    }
}
