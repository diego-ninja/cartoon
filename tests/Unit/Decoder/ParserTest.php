<?php

declare(strict_types=1);

namespace Toon\Tests\Unit\Decoder;

use PHPUnit\Framework\TestCase;
use Toon\AST\ArrayNode;
use Toon\AST\NodeType;
use Toon\AST\ObjectNode;
use Toon\AST\PrimitiveNode;
use Toon\DecodeOptions;
use Toon\Decoder\Parser;
use Toon\Decoder\Token;
use Toon\Decoder\TokenType;
use Toon\DelimiterType;
use Toon\Exception\SyntaxException;
use Toon\Exception\ValidationException;

final class ParserTest extends TestCase
{
    public function test_parse_empty_tokens(): void
    {
        $parser = new Parser(new DecodeOptions());
        $node = $parser->parse([]);

        $this->assertInstanceOf(ObjectNode::class, $node);
        $this->assertSame([], $node->toPhp());
    }

    public function test_parse_single_primitive(): void
    {
        $tokens = [
            new Token(TokenType::Primitive, 'hello', 0, 1),
        ];

        $parser = new Parser(new DecodeOptions());
        $node = $parser->parse($tokens);

        $this->assertInstanceOf(PrimitiveNode::class, $node);
        $this->assertSame('hello', $node->toPhp());
    }

    public function test_parse_simple_object(): void
    {
        $tokens = [
            new Token(TokenType::ObjectKey, 'name', 0, 1),
            new Token(TokenType::Primitive, 'Alice', 0, 1),
            new Token(TokenType::ObjectKey, 'age', 0, 2),
            new Token(TokenType::Primitive, '30', 0, 2),
        ];

        $parser = new Parser(new DecodeOptions());
        $node = $parser->parse($tokens);

        $this->assertInstanceOf(ObjectNode::class, $node);
        $result = $node->toPhp();
        $this->assertSame('Alice', $result['name']);
        $this->assertSame(30, $result['age']);
    }

    public function test_parse_array_header(): void
    {
        $tokens = [
            new Token(TokenType::ArrayHeader, 'items[3]:', 0, 1),
            new Token(TokenType::Primitive, 'a', 0, 1),
            new Token(TokenType::Primitive, 'b', 0, 1),
            new Token(TokenType::Primitive, 'c', 0, 1),
        ];

        $parser = new Parser(new DecodeOptions());
        $node = $parser->parse($tokens);

        $this->assertInstanceOf(ArrayNode::class, $node);
        $this->assertSame(['a', 'b', 'c'], $node->toPhp());
    }

    public function test_parse_nested_object(): void
    {
        $tokens = [
            new Token(TokenType::ObjectKey, 'user', 0, 1),
            new Token(TokenType::ObjectKey, 'name', 2, 2),
            new Token(TokenType::Primitive, 'Bob', 0, 2),
        ];

        $parser = new Parser(new DecodeOptions());
        $node = $parser->parse($tokens);

        $this->assertInstanceOf(ObjectNode::class, $node);
        $expected = [
            'user' => [
                'name' => 'Bob',
            ],
        ];
        $this->assertSame($expected, $node->toPhp());
    }

    public function test_parse_array_length_mismatch_strict_mode(): void
    {
        $tokens = [
            new Token(TokenType::ArrayHeader, '[2]:', 0, 1),
            new Token(TokenType::Primitive, 'a', 0, 1),
        ];

        $parser = new Parser(new DecodeOptions(strict: true));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Array length mismatch');
        $parser->parse($tokens);
    }

    public function test_parse_array_length_mismatch_permissive_mode(): void
    {
        $tokens = [
            new Token(TokenType::ArrayHeader, '[2]:', 0, 1),
            new Token(TokenType::Primitive, 'a', 0, 1),
        ];

        $parser = new Parser(new DecodeOptions(strict: false));
        $node = $parser->parse($tokens);

        $this->assertSame(['a'], $node->toPhp());
    }
}
