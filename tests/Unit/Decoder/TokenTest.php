<?php

declare(strict_types=1);

namespace Ninja\Cartoon\Tests\Unit\Decoder;

use PHPUnit\Framework\TestCase;
use Ninja\Cartoon\Decoder\Enum\TokenType;
use Ninja\Cartoon\Decoder\Token;

final class TokenTest extends TestCase
{
    public function test_token_creation(): void
    {
        $token = new Token(
            type: TokenType::ObjectKey,
            value: 'name',
            indentLevel: 0,
            lineNumber: 1,
        );

        $this->assertSame(TokenType::ObjectKey, $token->type);
        $this->assertSame('name', $token->value);
        $this->assertSame(0, $token->indentLevel);
        $this->assertSame(1, $token->lineNumber);
    }

    public function test_token_with_array_header(): void
    {
        $token = new Token(
            type: TokenType::ArrayHeader,
            value: '[3]:',
            indentLevel: 2,
            lineNumber: 5,
        );

        $this->assertSame(TokenType::ArrayHeader, $token->type);
        $this->assertSame('[3]:', $token->value);
        $this->assertSame(2, $token->indentLevel);
        $this->assertSame(5, $token->lineNumber);
    }
}
