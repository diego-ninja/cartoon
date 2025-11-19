<?php

// ABOUTME: Converts TOON text into a stream of tokens.
// ABOUTME: Detects line types, validates basic syntax, tracks indentation.

declare(strict_types=1);

namespace Toon\Decoder;

use Toon\DecodeOptions;
use Toon\Exception\SyntaxException;
use Toon\Decoder\Enum\TokenType;

final class Tokenizer
{
    private const string PATTERN_ARRAY_HEADER = '/^(\s*)([A-Za-z_][A-Za-z0-9_.]*|"(?:[^"\\\\]|\\\\.)*")?(\[\d+[,\t|]?\](?:\{[^}]+\})?):(.*)$/';
    private const string PATTERN_OBJECT_KEY = '/^(\s*)([A-Za-z_][A-Za-z0-9_.]*|"(?:[^"\\\\]|\\\\.)*"):\s*(.*)$/';
    private const string PATTERN_LIST_ITEM = '/^(\s*)-\s+(.+)$/';

    public function __construct(
        private readonly DecodeOptions $options,
    ) {}

    /**
     * @return array<int, Token>
     * @throws SyntaxException
     */
    public function tokenize(string $input): array
    {
        if ($input === '') {
            return [];
        }

        $lines = explode("\n", $input);
        $tokens = [];
        $lineNumber = 0;

        foreach ($lines as $line) {
            $lineNumber++;

            if (trim($line) === '') {
                $tokens[] = new Token(
                    type: TokenType::BlankLine,
                    value: '',
                    indentLevel: 0, // Blank lines have no meaningful indent level
                    lineNumber: $lineNumber,
                );
                continue;
            }

            $lineTokens = $this->tokenizeLine($line, $lineNumber);
            foreach ($lineTokens as $token) {
                $tokens[] = $token;
            }
        }

        return $tokens;
    }

    /**
     * @return array<int, Token>
     * @throws SyntaxException
     */
    private function tokenizeLine(string $line, int $lineNumber): array
    {
        $indentLevel = $this->calculateIndentLevel($line, $lineNumber);

        // Try array header
        if (preg_match(self::PATTERN_ARRAY_HEADER, $line, $matches)) {
            // ArrayHeader: trim needed for optional empty string case
            return [new Token(
                type: TokenType::ArrayHeader,
                value: trim($matches[2]) . $matches[3] . ':',
                indentLevel: $indentLevel,
                lineNumber: $lineNumber,
            )];
        }

        // Try list item
        if (preg_match(self::PATTERN_LIST_ITEM, $line, $matches)) {
            return [new Token(
                type: TokenType::ListItem,
                value: $matches[2],
                indentLevel: $indentLevel,
                lineNumber: $lineNumber,
            )];
        }

        // Try object key
        if (preg_match(self::PATTERN_OBJECT_KEY, $line, $matches)) {
            $tokens = [];
            // ObjectKey: No trim needed - regex captures clean (no leading/trailing spaces)
            $tokens[] = new Token(
                type: TokenType::ObjectKey,
                value: $matches[2],
                indentLevel: $indentLevel,
                lineNumber: $lineNumber,
            );

            // Check if there's an inline value
            if (trim($matches[3]) !== '') {
                $tokens[] = new Token(
                    type: TokenType::Primitive,
                    value: trim($matches[3]),
                    indentLevel: $indentLevel,
                    lineNumber: $lineNumber,
                );
            }

            return $tokens;
        }

        // Must be primitive
        return [new Token(
            type: TokenType::Primitive,
            value: trim($line),
            indentLevel: $indentLevel,
            lineNumber: $lineNumber,
        )];
    }

    /**
     * @throws SyntaxException
     */
    private function calculateIndentLevel(string $line, int $lineNumber): int
    {
        $len = strlen($line);
        $spaces = 0;

        for ($i = 0; $i < $len; $i++) {
            $char = $line[$i];
            if ($char === ' ') {
                $spaces++;
            } elseif ($char === "\t") {
                if ($this->options->strict) {
                    throw new SyntaxException('Tabs are not allowed in indentation', lineNumber: $lineNumber);
                }
                // In non-strict mode, tab width is implementation-defined. We'll treat it as `indentSize`.
                $spaces += $this->options->indentSize;
            } else {
                break;
            }
        }

        if ($this->options->strict && $this->options->indentSize > 0) {
            if ($spaces % $this->options->indentSize !== 0) {
                throw new SyntaxException(
                    "Indentation must be an exact multiple of {$this->options->indentSize} spaces",
                    lineNumber: $lineNumber,
                );
            }
        }

        if ($this->options->indentSize === 0) {
            return $spaces > 0 ? 1 : 0;
        }

        return (int) ($spaces / $this->options->indentSize);
    }
}
