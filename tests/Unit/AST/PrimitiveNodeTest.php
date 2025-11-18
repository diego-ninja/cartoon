<?php

declare(strict_types=1);

namespace Toon\Tests\Unit\AST;

use PHPUnit\Framework\TestCase;
use Toon\AST\NodeType;
use Toon\AST\PrimitiveNode;

final class PrimitiveNodeTest extends TestCase
{
    public function test_primitive_node_string(): void
    {
        $node = new PrimitiveNode('hello');

        $this->assertSame(NodeType::Primitive, $node->getType());
        $this->assertSame('hello', $node->toPhp());
    }

    public function test_primitive_node_integer(): void
    {
        $node = new PrimitiveNode(42);

        $this->assertSame(42, $node->toPhp());
    }

    public function test_primitive_node_float(): void
    {
        $node = new PrimitiveNode(3.14);

        $this->assertSame(3.14, $node->toPhp());
    }

    public function test_primitive_node_boolean(): void
    {
        $node = new PrimitiveNode(true);

        $this->assertTrue($node->toPhp());
    }

    public function test_primitive_node_null(): void
    {
        $node = new PrimitiveNode(null);

        $this->assertNull($node->toPhp());
    }
}
