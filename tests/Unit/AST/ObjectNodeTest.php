<?php

declare(strict_types=1);

namespace Ninja\Cartoon\Tests\Unit\AST;

use PHPUnit\Framework\TestCase;
use Ninja\Cartoon\AST\NodeType;
use Ninja\Cartoon\AST\ObjectNode;
use Ninja\Cartoon\AST\PrimitiveNode;

final class ObjectNodeTest extends TestCase
{
    public function test_object_node_empty(): void
    {
        $node = new ObjectNode([]);

        $this->assertSame(NodeType::Object, $node->getType());
        $this->assertSame([], $node->toPhp());
    }

    public function test_object_node_with_properties(): void
    {
        $node = new ObjectNode([
            'name' => new PrimitiveNode('Alice'),
            'age' => new PrimitiveNode(30),
        ]);

        $expected = [
            'name' => 'Alice',
            'age' => 30,
        ];

        $this->assertSame($expected, $node->toPhp());
    }

    public function test_object_node_nested(): void
    {
        $node = new ObjectNode([
            'user' => new ObjectNode([
                'name' => new PrimitiveNode('Bob'),
            ]),
        ]);

        $expected = [
            'user' => [
                'name' => 'Bob',
            ],
        ];

        $this->assertSame($expected, $node->toPhp());
    }
}
