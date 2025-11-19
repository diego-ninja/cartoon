<?php

declare(strict_types=1);

// ABOUTME: Represents an array (ordered sequence) in the AST.
// ABOUTME: Tracks delimiter type and declared length for validation.

namespace Toon\AST;

use Toon\Enum\DelimiterType;

final readonly class ArrayNode implements Node
{
    /**
     * @param array<int, Node> $items
     */
    public function __construct(
        private array $items,
        private DelimiterType $delimiter,
        private int $declaredLength,
    ) {}

    public function getType(): NodeType
    {
        return NodeType::Array;
    }

    /**
     * @return array<int, mixed>
     */
    public function toPhp(): array
    {
        $result = [];
        foreach ($this->items as $node) {
            $result[] = $node->toPhp();
        }
        return $result;
    }

    public function getDelimiter(): DelimiterType
    {
        return $this->delimiter;
    }

    public function getDeclaredLength(): int
    {
        return $this->declaredLength;
    }

    /**
     * @return array<int, Node>
     */
    public function getItems(): array
    {
        return $this->items;
    }
}
