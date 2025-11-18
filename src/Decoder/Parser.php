<?php

// ABOUTME: Builds AST from token stream.
// ABOUTME: Handles nested structures, array headers, and validation.


namespace Toon\Decoder;

use Toon\AST\ArrayNode;
use Toon\AST\Node;
use Toon\AST\ObjectNode;
use Toon\AST\PrimitiveNode;
use Toon\DecodeOptions;
use Toon\Decoder\Enum\RootType;
use Toon\Decoder\Enum\TokenType;
use Toon\Decoder\ValueParser;
use Toon\Enum\DelimiterType;
use Toon\Exception\EscapeException;
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
     * @return Node
     * @throws EscapeException
     * @throws SyntaxException
     * @throws ValidationException
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

    /**
     * @throws EscapeException
     */
    private function parsePrimitive(Token $token): PrimitiveNode
    {
        $value = $this->valueParser->parse($token->value);
        return new PrimitiveNode($value);
    }

    /**
     * @param array<int, Token> $tokens
     * @throws SyntaxException
     * @throws EscapeException|ValidationException
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
            if ($token->indentLevel > $baseIndent) {
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

                    // Check if next token is nested (higher indent) or inline (same indent, same line)
                    if ($nextToken->indentLevel > $token->indentLevel) {
                        // Nested object or array
                        if ($nextToken->type === TokenType::ArrayHeader) {
                            $properties[$key] = $this->parseArray($tokens, $i + 1, $nextToken->indentLevel);
                        } else {
                            $properties[$key] = $this->parseObject($tokens, $i + 1, $nextToken->indentLevel);
                        }
                    } elseif ($nextToken->indentLevel === $token->indentLevel && $nextToken->lineNumber === $token->lineNumber) {
                        // Inline value (on same line)
                        $properties[$key] = $this->parsePrimitive($nextToken);
                        $i++; // Skip the value token
                    }
                }
            }

            $i++;
        }

        return new ObjectNode($properties);
    }

    /**
     * @param array<int, Token> $tokens
     * @return ArrayNode
     * @throws SyntaxException
     * @throws ValidationException
     * @throws EscapeException
     */
    private function parseRootArray(array $tokens): ArrayNode
    {
        return $this->parseArray($tokens, 0, 0);
    }

    /**
     * @param array<int, Token> $tokens
     * @throws ValidationException
     * @throws SyntaxException|EscapeException
     */
    private function parseArray(array $tokens, int $startIndex, int $baseIndent): ArrayNode
    {
        $headerToken = $tokens[$startIndex];

        // Parse array header to extract length and delimiter
        preg_match('/\[(\d+)([,\t|])?\](?:\{([^}]+)\})?:/', $headerToken->value, $matches);
        $declaredLength = (int) $matches[1];
        $delimiterChar = empty($matches[2]) ? ',' : $matches[2];
        $delimiter = DelimiterType::from($delimiterChar);
        $fieldListString = $matches[3] ?? null; // Capture the field list string

        $expectedFieldCount = -1; // -1 means no field list
        if ($fieldListString !== null) {
            $fields = $this->valueParser->parseDelimitedValues($fieldListString, $delimiterChar);
            $expectedFieldCount = count($fields);
        }

        // Collect array items
        $items = [];
        $i = $startIndex + 1;

        while ($i < count($tokens) && count($items) < $declaredLength) {
            start_item_loop:
            $token = $tokens[$i];

            if ($token->type === TokenType::BlankLine) {
                if ($this->options->strict) {
                    throw new SyntaxException(
                        'Blank lines are not allowed inside arrays in strict mode',
                        lineNumber: $token->lineNumber,
                    );
                }
                $i++;
                continue;
            }

            if ($token->indentLevel < $baseIndent) {
                break;
            }

            // Ignore list markers, the real content is in the following tokens
            if ($token->type === TokenType::ListItem) {
                $i++;
                continue;
            }

            // The first non-list-marker token determines the indent level for all items.
            if (!isset($contentIndent)) {
                $contentIndent = $token->indentLevel;
            }

            if ($token->indentLevel !== $contentIndent) {
                $i++;
                continue;
            }

            if ($token->type === TokenType::Primitive) {
                if ($this->options->strict && $expectedFieldCount !== -1) {
                    $rowValues = $this->valueParser->parseDelimitedValues($token->value, $delimiterChar);
                    $valueCount = count($rowValues);

                    if ($valueCount !== $expectedFieldCount) {
                        throw new SyntaxException(
                            "Tabular row width mismatch: declared {$expectedFieldCount} fields, found {$valueCount} value(s)",
                            lineNumber: $token->lineNumber,
                        );
                    }
                }
                $items[] = $this->parsePrimitive($token);
            } elseif ($token->type === TokenType::ObjectKey) {
                // This is an object in the array. Parse it.
                $objectNode = $this->parseObject($tokens, $i, $token->indentLevel);
                $items[] = $objectNode;

                // Now, we must advance $i past all the tokens this object just consumed.
                // We find the next token that is NOT a child of this object.
                $startIndex = $i + 1;
                while ($startIndex < count($tokens)) {
                    if ($tokens[$startIndex]->indentLevel < $token->indentLevel) {
                        $i = $startIndex;
                        goto start_item_loop;
                    }
                    $startIndex++;
                }
                $i = $startIndex;
                continue;
            }

            $i++;
        }

        // Validate length in strict mode
        if ($this->options->strict && count($items) !== $declaredLength) {
            throw new ValidationException(
                "Array length mismatch: declared $declaredLength, found " . count($items),
                lineNumber: $headerToken->lineNumber,
            );
        }

        return new ArrayNode($items, $delimiter, $declaredLength);
    }
}
