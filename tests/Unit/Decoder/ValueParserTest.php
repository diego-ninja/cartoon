<?php

declare(strict_types=1);

namespace Ninja\Cartoon\Tests\Unit\Decoder;

use PHPUnit\Framework\TestCase;
use Ninja\Cartoon\Decoder\ValueParser;
use Ninja\Cartoon\Exception\EscapeException;
use Ninja\Cartoon\Exception\SyntaxException;

final class ValueParserTest extends TestCase
{
    private ValueParser $parser;

    protected function setUp(): void
    {
        $this->parser = new ValueParser();
    }

    public function test_parse_string_unquoted(): void
    {
        $this->assertSame('hello', $this->parser->parse('hello'));
    }

    public function test_parse_string_quoted(): void
    {
        $this->assertSame('hello world', $this->parser->parse('"hello world"'));
    }

    public function test_parse_string_with_escapes(): void
    {
        $this->assertSame("hello\nworld", $this->parser->parse('"hello\\nworld"'));
        $this->assertSame("tab\there", $this->parser->parse('"tab\\there"'));
        $this->assertSame('quote"here', $this->parser->parse('"quote\\"here"'));
        $this->assertSame('back\\slash', $this->parser->parse('"back\\\\slash"'));
        $this->assertSame("return\rhere", $this->parser->parse('"return\\rhere"'));
    }

    public function test_parse_string_invalid_escape_throws(): void
    {
        $this->expectException(EscapeException::class);
        $this->parser->parse('"invalid\\x"');
    }

    public function test_parse_integer(): void
    {
        $this->assertSame(42, $this->parser->parse('42'));
        $this->assertSame(-17, $this->parser->parse('-17'));
        $this->assertSame(0, $this->parser->parse('0'));
    }

    public function test_parse_float(): void
    {
        $this->assertSame(3.14, $this->parser->parse('3.14'));
        $this->assertSame(-2.5, $this->parser->parse('-2.5'));
        $this->assertSame(0.5, $this->parser->parse('0.5'));
    }

    public function test_parse_boolean(): void
    {
        $this->assertTrue($this->parser->parse('true'));
        $this->assertFalse($this->parser->parse('false'));
    }

    public function test_parse_null(): void
    {
        $this->assertNull($this->parser->parse('null'));
    }

    public function test_parse_empty_string(): void
    {
        $this->assertSame('', $this->parser->parse('""'));
    }
}
