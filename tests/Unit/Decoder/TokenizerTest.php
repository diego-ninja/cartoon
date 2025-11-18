<?php

declare(strict_types=1);

namespace Toon\Tests\Unit\Decoder;

use PHPUnit\Framework\TestCase;
use Toon\Decoder\TokenType;
use Toon\Decoder\Tokenizer;

final class TokenizerTest extends TestCase
{
    public function test_tokenize_empty_string(): void
    {
        $tokenizer = new Tokenizer();
        $tokens = $tokenizer->tokenize('');

        $this->assertCount(0, $tokens);
    }

    public function test_tokenize_simple_object_key(): void
    {
        $tokenizer = new Tokenizer();
        $tokens = $tokenizer->tokenize('name: Alice');

        $this->assertCount(1, $tokens);
        $this->assertSame(TokenType::ObjectKey, $tokens[0]->type);
        $this->assertSame('name', $tokens[0]->value);
        $this->assertSame(0, $tokens[0]->indentLevel);
        $this->assertSame(1, $tokens[0]->lineNumber);
    }

    public function test_tokenize_indented_key(): void
    {
        $tokenizer = new Tokenizer();
        $tokens = $tokenizer->tokenize('  age: 30');

        $this->assertCount(1, $tokens);
        $this->assertSame(TokenType::ObjectKey, $tokens[0]->type);
        $this->assertSame('age', $tokens[0]->value);
        $this->assertSame(2, $tokens[0]->indentLevel);
    }

    public function test_tokenize_array_header(): void
    {
        $tokenizer = new Tokenizer();
        $tokens = $tokenizer->tokenize('items[3]:');

        $this->assertCount(1, $tokens);
        $this->assertSame(TokenType::ArrayHeader, $tokens[0]->type);
        $this->assertSame('items[3]:', $tokens[0]->value);
    }

    public function test_tokenize_list_item(): void
    {
        $tokenizer = new Tokenizer();
        $tokens = $tokenizer->tokenize('- value');

        $this->assertCount(1, $tokens);
        $this->assertSame(TokenType::ListItem, $tokens[0]->type);
        $this->assertSame('value', $tokens[0]->value);
    }

    public function test_tokenize_primitive(): void
    {
        $tokenizer = new Tokenizer();
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

        $tokenizer = new Tokenizer();
        $tokens = $tokenizer->tokenize($input);

        $this->assertCount(2, $tokens);
        $this->assertSame('name', $tokens[0]->value);
        $this->assertSame('age', $tokens[1]->value);
        $this->assertSame(2, $tokens[1]->lineNumber);
    }

    public function test_tokenize_skips_empty_lines(): void
    {
        $input = <<<TOON
name: Alice

age: 30
TOON;

        $tokenizer = new Tokenizer();
        $tokens = $tokenizer->tokenize($input);

        $this->assertCount(2, $tokens);
    }
}
