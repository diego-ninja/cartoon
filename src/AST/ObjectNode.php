<?php

// ABOUTME: Represents an object (key-value mapping) in the AST.
// ABOUTME: Converts to PHP associative array.

declare(strict_types=1);

namespace Ninja\Cartoon\AST;

final readonly class ObjectNode implements Node
{
    /**
     * @param array<string, Node> $properties
     */
    public function __construct(
        private array $properties,
    ) {}

    public function getType(): NodeType
    {
        return NodeType::Object;
    }

    /**
     * @return array<string, mixed>
     */
    public function toPhp(): array
    {
        $result = [];
        foreach ($this->properties as $key => $node) {
            $result[$key] = $node->toPhp();
        }
        return $result;
    }
}
