<?php

declare(strict_types=1);

namespace Toon\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Toon\AST\NodeType;
use Toon\Decoder\Enum\RootType;
use Toon\Enum\DelimiterType;
use Toon\Enum\IndentationType;

final class EnumTest extends TestCase
{
    public function test_delimiter_type_has_correct_values(): void
    {
        $this->assertSame(',', DelimiterType::Comma->value);
        $this->assertSame("\t", DelimiterType::Tab->value);
        $this->assertSame('|', DelimiterType::Pipe->value);
    }

    public function test_indentation_type_has_cases(): void
    {
        $this->assertSame('Spaces', IndentationType::Spaces->name);
        $this->assertSame('Tabs', IndentationType::Tabs->name);
    }

    public function test_node_type_has_cases(): void
    {
        $this->assertSame('Object', NodeType::Object->name);
        $this->assertSame('Array', NodeType::Array->name);
        $this->assertSame('Primitive', NodeType::Primitive->name);
    }

    public function test_root_type_has_cases(): void
    {
        $this->assertSame('Object', RootType::Object->name);
        $this->assertSame('Array', RootType::Array->name);
        $this->assertSame('Primitive', RootType::Primitive->name);
    }
}
