<?php

declare(strict_types=1);

namespace Ninja\Cartoon\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ninja\Cartoon\DecodeOptions;
use Ninja\Cartoon\EncodeOptions;
use Ninja\Cartoon\Enum\DelimiterType;
use Ninja\Cartoon\Enum\IndentationType;

final class OptionsTest extends TestCase
{
    public function test_encode_options_has_defaults(): void
    {
        $options = new EncodeOptions();

        $this->assertSame(DelimiterType::Comma, $options->preferredDelimiter);
        $this->assertSame(2, $options->indentSize);
        $this->assertSame(IndentationType::Spaces, $options->indentationType);
        $this->assertSame(10, $options->maxCompactArrayLength);
    }

    public function test_encode_options_accepts_custom_values(): void
    {
        $options = new EncodeOptions(
            preferredDelimiter: DelimiterType::Tab,
            indentSize: 4,
            indentationType: IndentationType::Tabs,
            maxCompactArrayLength: 20,
        );

        $this->assertSame(DelimiterType::Tab, $options->preferredDelimiter);
        $this->assertSame(4, $options->indentSize);
        $this->assertSame(IndentationType::Tabs, $options->indentationType);
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

    public function test_encode_options_default_factory(): void
    {
        $options = EncodeOptions::default();

        $this->assertSame(DelimiterType::Comma, $options->preferredDelimiter);
        $this->assertSame(2, $options->indentSize);
        $this->assertSame(IndentationType::Spaces, $options->indentationType);
        $this->assertSame(10, $options->maxCompactArrayLength);
    }

    public function test_decode_options_default_factory(): void
    {
        $options = DecodeOptions::default();

        $this->assertTrue($options->strict);
        $this->assertTrue($options->preserveKeyOrder);
        $this->assertSame(2, $options->indentSize);
    }
}
