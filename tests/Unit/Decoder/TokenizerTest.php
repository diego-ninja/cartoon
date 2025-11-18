<?php

declare(strict_types=1);

namespace Toon\Tests\Unit\Decoder;

use PHPUnit\Framework\TestCase;
use Toon\DecodeOptions;
use Toon\Decoder\Enum\TokenType;
use Toon\Decoder\Tokenizer;

final class TokenizerTest extends TestCase
{
    public function test_tokenize_empty_string(): void
    {
        $tokenizer = new Tokenizer(new DecodeOptions());
        $tokens = $tokenizer->tokenize('');

        $this->assertCount(0, $tokens);
    }

    public function test_tokenize_simple_object_key(): void
    {
        $tokenizer = new Tokenizer(new DecodeOptions());
        $tokens = $tokenizer->tokenize('name: Alice');

        $this->assertCount(2, $tokens);
        $this->assertSame(TokenType::ObjectKey, $tokens[0]->type);
        $this->assertSame('name', $tokens[0]->value);
        $this->assertSame(0, $tokens[0]->indentLevel);
        $this->assertSame(1, $tokens[0]->lineNumber);
        $this->assertSame(TokenType::Primitive, $tokens[1]->type);
        $this->assertSame('Alice', $tokens[1]->value);
        $this->assertSame(0, $tokens[1]->indentLevel);
        $this->assertSame(1, $tokens[1]->lineNumber);
    }

    public function test_tokenize_indented_key(): void
    {
        $tokenizer = new Tokenizer(new DecodeOptions());
        $tokens = $tokenizer->tokenize('  age: 30');

        $this->assertCount(2, $tokens);
        $this->assertSame(TokenType::ObjectKey, $tokens[0]->type);
        $this->assertSame('age', $tokens[0]->value);
        $this->assertSame(1, $tokens[0]->indentLevel); // 1 indent level (2 spaces)
        $this->assertSame(TokenType::Primitive, $tokens[1]->type);
        $this->assertSame('30', $tokens[1]->value);
        $this->assertSame(1, $tokens[1]->indentLevel);
    }

    public function test_tokenize_array_header(): void
    {
        $tokenizer = new Tokenizer(new DecodeOptions());
        $tokens = $tokenizer->tokenize('items[3]:');

        $this->assertCount(1, $tokens);
        $this->assertSame(TokenType::ArrayHeader, $tokens[0]->type);
        $this->assertSame('items[3]:', $tokens[0]->value);
    }

    public function test_tokenize_list_item(): void
    {
        $tokenizer = new Tokenizer(new DecodeOptions());
        $tokens = $tokenizer->tokenize('- value');

        $this->assertCount(1, $tokens);
        $this->assertSame(TokenType::ListItem, $tokens[0]->type);
        $this->assertSame('value', $tokens[0]->value);
    }

    public function test_tokenize_primitive(): void
    {
        $tokenizer = new Tokenizer(new DecodeOptions());
        $tokens = $tokenizer->tokenize('hello');

        $this->assertCount(1, $tokens);
        $this->assertSame(TokenType::Primitive, $tokens[0]->type);
        $this->assertSame('hello', $tokens[0]->value);
    }

    public function test_tokenize_multiple_lines(): void
    {
        $input = <<<TOON
name: Alice
  age: 30
TOON;

        $tokenizer = new Tokenizer(new DecodeOptions());
        $tokens = $tokenizer->tokenize($input);

        $this->assertCount(4, $tokens);
        $this->assertSame('name', $tokens[0]->value);
        $this->assertSame('Alice', $tokens[1]->value);
        $this->assertSame('age', $tokens[2]->value);
        $this->assertSame('30', $tokens[3]->value);
        $this->assertSame(1, $tokens[2]->indentLevel); // Indent level is 1
        $this->assertSame(2, $tokens[2]->lineNumber);
    }

    public function test_tokenize_skips_empty_lines(): void
    {
        $input = <<<TOON
name: Alice

age: 30
TOON;

        $tokenizer = new Tokenizer(new DecodeOptions());
        $tokens = $tokenizer->tokenize($input);

        // Now emits BlankLine tokens: ObjectKey, Primitive, BlankLine, ObjectKey, Primitive
        $this->assertCount(5, $tokens);
        $this->assertSame(TokenType::BlankLine, $tokens[2]->type);
    }

    public function test_tokenize_mixed_tab_and_spaces(): void
    {
        $tokenizer = new Tokenizer(new DecodeOptions(strict: false, indentSize: 2));
        $tokens = $tokenizer->tokenize("\t  name: value");  // tab (2 levels) + 2 spaces (1 level) = 3 levels

        $this->assertCount(2, $tokens);
        $this->assertSame(2, $tokens[0]->indentLevel); // 2 levels
    }
}
