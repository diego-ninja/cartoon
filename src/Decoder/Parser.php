<?php

// ABOUTME: Builds AST from token stream.
// ABOUTME: Handles nested structures, array headers, and validation.

declare(strict_types=1);

namespace Toon\Decoder;

use Toon\AST\ArrayNode;
use Toon\AST\Node;
use Toon\AST\ObjectNode;
use Toon\AST\PrimitiveNode;
use Toon\DecodeOptions;
use Toon\DelimiterType;
use Toon\Exception\SyntaxException;
use Toon\Exception\ValidationException;

final class Parser
{
    private ValueParser $valueParser;

    public function __construct(
        private readonly DecodeOptions $options,
    ) {
        $this->valueParser = new ValueParser();
    }

    /**
     * @param array<int, Token> $tokens
     */
    public function parse(array $tokens): Node
    {
        if (empty($tokens)) {
            return new ObjectNode([]);
        }

        // Detect root type
        $rootType = $this->detectRootType($tokens);

        return match ($rootType) {
            RootType::Primitive => $this->parsePrimitive($tokens[0]),
            RootType::Array => $this->parseRootArray($tokens),
            RootType::Object => $this->parseObject($tokens, 0),
        };
    }

    /**
     * @param array<int, Token> $tokens
     */
    private function detectRootType(array $tokens): RootType
    {
        $firstToken = $tokens[0];

        if ($firstToken->type === TokenType::ArrayHeader) {
            return RootType::Array;
        }

        if ($firstToken->type === TokenType::Primitive && count($tokens) === 1) {
            return RootType::Primitive;
        }

        return RootType::Object;
    }

    private function parsePrimitive(Token $token): PrimitiveNode
    {
        $value = $this->valueParser->parse($token->value);
        return new PrimitiveNode($value);
    }

    /**
     * @param array<int, Token> $tokens
     */
    private function parseObject(array $tokens, int $startIndex, int $baseIndent = 0): ObjectNode
    {
        $properties = [];
        $i = $startIndex;

        while ($i < count($tokens)) {
            $token = $tokens[$i];

            // Stop if we've dedented back to parent level
            if ($token->indentLevel < $baseIndent) {
                break;
            }

            // Skip tokens not at our level
            if ($token->indentLevel > $baseIndent && $token->type !== TokenType::ObjectKey) {
                $i++;
                continue;
            }

            if ($token->type === TokenType::ObjectKey) {
                $key = $this->valueParser->parse($token->value);
                if (!is_string($key)) {
                    throw new SyntaxException("Object key must be string, got " . gettype($key), lineNumber: $token->lineNumber);
                }

                // Look ahead for value
                if ($i + 1 < count($tokens)) {
                    $nextToken = $tokens[$i + 1];

                    // Check if next token is nested (higher indent)
                    if ($nextToken->indentLevel > $token->indentLevel) {
                        // Nested object or array
                        if ($nextToken->type === TokenType::ArrayHeader) {
                            $properties[$key] = $this->parseArray($tokens, $i + 1, $nextToken->indentLevel);
                        } else {
                            $properties[$key] = $this->parseObject($tokens, $i + 1, $nextToken->indentLevel);
                        }
                    } else {
                        // Inline value (on same line, stored in token)
                        // For now, we'll skip inline values as the tokenizer needs enhancement
                        $i++;
                        continue;
                    }
                }
            }

            $i++;
        }

        return new ObjectNode($properties);
    }

    /**
     * @param array<int, Token> $tokens
     */
    private function parseRootArray(array $tokens): ArrayNode
    {
        return $this->parseArray($tokens, 0, 0);
    }

    /**
     * @param array<int, Token> $tokens
     */
    private function parseArray(array $tokens, int $startIndex, int $baseIndent): ArrayNode
    {
        $headerToken = $tokens[$startIndex];

        // Parse array header to extract length and delimiter
        preg_match('/\[(\d+)([,\t|])?\]/', $headerToken->value, $matches);
        $declaredLength = (int) $matches[1];
        $delimiterChar = $matches[2] ?? ',';

        $delimiter = match ($delimiterChar) {
            ',' => DelimiterType::Comma,
            "\t" => DelimiterType::Tab,
            '|' => DelimiterType::Pipe,
            default => DelimiterType::Comma,
        };

        // Collect array items
        $items = [];
        $i = $startIndex + 1;

        while ($i < count($tokens) && count($items) < $declaredLength) {
            $token = $tokens[$i];

            if ($token->indentLevel < $baseIndent) {
                break;
            }

            if ($token->type === TokenType::Primitive || $token->type === TokenType::ListItem) {
                $items[] = $this->parsePrimitive($token);
            } elseif ($token->type === TokenType::ObjectKey) {
                // This is an object in the array
                $items[] = $this->parseObject($tokens, $i, $token->indentLevel);
            }

            $i++;
        }

        // Validate length in strict mode
        if ($this->options->strict && count($items) !== $declaredLength) {
            throw new ValidationException(
                "Array length mismatch: declared {$declaredLength}, found " . count($items),
                lineNumber: $headerToken->lineNumber,
            );
        }

        return new ArrayNode($items, $delimiter, $declaredLength);
    }
}
