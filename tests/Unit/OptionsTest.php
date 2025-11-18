<?php

declare(strict_types=1);

namespace Toon\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Toon\DecodeOptions;
use Toon\DelimiterType;
use Toon\EncodeOptions;
use Toon\IndentationType;

final class OptionsTest extends TestCase
{
    public function test_encode_options_has_defaults(): void
    {
        $options = new EncodeOptions();

        $this->assertSame(DelimiterType::Comma, $options->preferredDelimiter);
        $this->assertSame(2, $options->indentSize);
        $this->assertSame(IndentationType::Spaces, $options->indentationType);
        $this->assertTrue($options->prettyArrays);
        $this->assertSame(10, $options->maxCompactArrayLength);
    }

    public function test_encode_options_accepts_custom_values(): void
    {
        $options = new EncodeOptions(
            preferredDelimiter: DelimiterType::Tab,
            indentSize: 4,
            indentationType: IndentationType::Tabs,
            prettyArrays: false,
            maxCompactArrayLength: 20,
        );

        $this->assertSame(DelimiterType::Tab, $options->preferredDelimiter);
        $this->assertSame(4, $options->indentSize);
        $this->assertSame(IndentationType::Tabs, $options->indentationType);
        $this->assertFalse($options->prettyArrays);
        $this->assertSame(20, $options->maxCompactArrayLength);
    }

    public function test_decode_options_has_defaults(): void
    {
        $options = new DecodeOptions();

        $this->assertTrue($options->strict);
        $this->assertTrue($options->preserveKeyOrder);
    }

    public function test_decode_options_accepts_custom_values(): void
    {
        $options = new DecodeOptions(
            strict: false,
            preserveKeyOrder: false,
        );

        $this->assertFalse($options->strict);
        $this->assertFalse($options->preserveKeyOrder);
    }
}
