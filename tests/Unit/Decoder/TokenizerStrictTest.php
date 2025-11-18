<?php

declare(strict_types=1);

namespace Toon\Tests\Unit\Decoder;

use PHPUnit\Framework\TestCase;
use Toon\DecodeOptions;
use Toon\Decoder\Tokenizer;
use Toon\Exception\SyntaxException;

final class TokenizerStrictTest extends TestCase
{
    public function test_tab_in_indentation_throws_in_strict_mode(): void
    {
        $toon = "key:\n\t- item"; // Indented with a tab

        $tokenizer = new Tokenizer(new DecodeOptions(strict: true));

        $this->expectException(SyntaxException::class);
        $this->expectExceptionMessage('Tabs are not allowed in indentation');

        $tokenizer->tokenize($toon);
    }

    public function test_uneven_indentation_throws_in_strict_mode(): void
    {
        $toon = "key:\n - item"; // Indented with 1 space, but indentSize is 2

        $tokenizer = new Tokenizer(new DecodeOptions(strict: true, indentSize: 2));

        $this->expectException(SyntaxException::class);
        $this->expectExceptionMessage('Indentation must be an exact multiple of 2 spaces');

        $tokenizer->tokenize($toon);
    }
}
