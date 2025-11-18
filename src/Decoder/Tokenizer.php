<?php

// ABOUTME: Converts TOON text into a stream of tokens.
// ABOUTME: Detects line types, validates basic syntax, tracks indentation.

declare(strict_types=1);

namespace Toon\Decoder;

final class Tokenizer
{
    private const string PATTERN_ARRAY_HEADER = '/^(\s*)([A-Za-z_][A-Za-z0-9_.]*|"(?:[^"\\\\]|\\\\.)*")?(\[\d+[,\t|]?\](?:\{[^}]+\})?):(.*)$/';
    private const string PATTERN_OBJECT_KEY = '/^(\s*)([A-Za-z_][A-Za-z0-9_.]*|"(?:[^"\\\\]|\\\\.)*"):\s*(.*)$/';
    private const string PATTERN_LIST_ITEM = '/^(\s*)-\s+(.+)$/';

    /**
     * @return array<int, Token>
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

            // Skip empty lines
            if (trim($line) === '') {
                continue;
            }

            $tokens[] = $this->tokenizeLine($line, $lineNumber);
        }

        return $tokens;
    }

    private function tokenizeLine(string $line, int $lineNumber): Token
    {
        $indentLevel = $this->calculateIndentLevel($line);

        // Try array header
        if (preg_match(self::PATTERN_ARRAY_HEADER, $line, $matches)) {
            return new Token(
                type: TokenType::ArrayHeader,
                value: trim($matches[2]) . $matches[3] . ':',
                indentLevel: $indentLevel,
                lineNumber: $lineNumber,
            );
        }

        // Try list item
        if (preg_match(self::PATTERN_LIST_ITEM, $line, $matches)) {
            return new Token(
                type: TokenType::ListItem,
                value: $matches[2],
                indentLevel: $indentLevel,
                lineNumber: $lineNumber,
            );
        }

        // Try object key
        if (preg_match(self::PATTERN_OBJECT_KEY, $line, $matches)) {
            return new Token(
                type: TokenType::ObjectKey,
                value: $matches[2],
                indentLevel: $indentLevel,
                lineNumber: $lineNumber,
            );
        }

        // Must be primitive
        return new Token(
            type: TokenType::Primitive,
            value: trim($line),
            indentLevel: $indentLevel,
            lineNumber: $lineNumber,
        );
    }

    private function calculateIndentLevel(string $line): int
    {
        $len = strlen($line);
        $spaces = 0;

        for ($i = 0; $i < $len; $i++) {
            if ($line[$i] === ' ') {
                $spaces++;
            } elseif ($line[$i] === "\t") {
                $spaces += 4; // Count tab as 4 spaces
            } else {
                break;
            }
        }

        return $spaces;
    }
}
