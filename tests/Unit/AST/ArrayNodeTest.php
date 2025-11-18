<?php

declare(strict_types=1);

namespace Toon\Tests\Unit\AST;

use PHPUnit\Framework\TestCase;
use Toon\AST\ArrayNode;
use Toon\AST\NodeType;
use Toon\AST\ObjectNode;
use Toon\AST\PrimitiveNode;
use Toon\Enum\DelimiterType;

final class ArrayNodeTest extends TestCase
{
    public function test_array_node_empty(): void
    {
        $node = new ArrayNode([], DelimiterType::Comma, 0);

        $this->assertSame(NodeType::Array, $node->getType());
        $this->assertSame([], $node->toPhp());
    }

    public function test_array_node_primitives(): void
    {
        $node = new ArrayNode([
            new PrimitiveNode(1),
            new PrimitiveNode(2),
            new PrimitiveNode(3),
        ], DelimiterType::Comma, 3);

        $this->assertSame([1, 2, 3], $node->toPhp());
    }

    public function test_array_node_objects(): void
    {
        $node = new ArrayNode([
            new ObjectNode([
                'id' => new PrimitiveNode(1),
                'name' => new PrimitiveNode('Alice'),
            ]),
            new ObjectNode([
                'id' => new PrimitiveNode(2),
                'name' => new PrimitiveNode('Bob'),
            ]),
        ], DelimiterType::Comma, 2);

        $expected = [
            ['id' => 1, 'name' => 'Alice'],
            ['id' => 2, 'name' => 'Bob'],
        ];

        $this->assertSame($expected, $node->toPhp());
    }

    public function test_array_node_preserves_delimiter_type(): void
    {
        $node = new ArrayNode(
            [new PrimitiveNode('a'), new PrimitiveNode('b')],
            DelimiterType::Tab,
            2
        );

        $this->assertSame(DelimiterType::Tab, $node->getDelimiter());
    }

    public function test_array_node_preserves_declared_length(): void
    {
        $node = new ArrayNode(
            [new PrimitiveNode('a')],
            DelimiterType::Comma,
            1
        );

        $this->assertSame(1, $node->getDeclaredLength());
    }

    public function test_array_node_get_items(): void
    {
        $items = [
            new PrimitiveNode(1),
            new PrimitiveNode(2),
        ];

        $node = new ArrayNode($items, DelimiterType::Comma, 2);

        $this->assertSame($items, $node->getItems());
    }
}
