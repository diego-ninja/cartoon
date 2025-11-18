<?php

// ABOUTME: Parses TOON primitive values into PHP types.
// ABOUTME: Handles strings with escaping, numbers, booleans, and null.

declare(strict_types=1);

namespace Toon\Decoder;

use Toon\Exception\EscapeException;

final class ValueParser
{
    private const array VALID_ESCAPES = ['\\\\', '\\"', '\\n', '\\r', '\\t'];
    private const array ESCAPE_REPLACEMENTS = ['\\', '"', "\n", "\r", "\t"];

    /**
     * @throws EscapeException
     */
    public function parse(string $value): string|int|float|bool|null
    {
        $trimmed = trim($value);

        // Handle quoted strings
        if (str_starts_with($trimmed, '"') && str_ends_with($trimmed, '"')) {
            return $this->parseQuotedString($trimmed);
        }

        // Handle boolean
        if ($trimmed === 'true') {
            return true;
        }
        if ($trimmed === 'false') {
            return false;
        }

        // Handle null
        if ($trimmed === 'null') {
            return null;
        }

        // Try to parse as number
        if (is_numeric($trimmed)) {
            if (str_contains($trimmed, '.')) {
                return (float) $trimmed;
            }
            return (int) $trimmed;
        }

        // Return as string
        return $trimmed;
    }

    /**
     * @param non-empty-string $delimiter
     * @return array<int, string|int|float|bool|null>
     * @throws EscapeException
     */
    public function parseDelimitedValues(string $valueString, string $delimiter): array
    {
        $values = explode($delimiter, $valueString);
        $parsedValues = [];
        foreach ($values as $value) {
            $parsedValues[] = $this->parse(trim($value));
        }
        return $parsedValues;
    }


    /**
     * @throws EscapeException
     */
    private function parseQuotedString(string $quoted): string
    {
        // Remove surrounding quotes
        $content = substr($quoted, 1, -1);

        // Validate escapes
        $this->validateEscapes($content);

        // Replace valid escapes
        return str_replace(self::VALID_ESCAPES, self::ESCAPE_REPLACEMENTS, $content);
    }

    /**
     * @throws EscapeException
     */
    private function validateEscapes(string $content): void
    {
        $pos = 0;
        $len = strlen($content);

        while ($pos < $len) {
            if ($content[$pos] === '\\') {
                if ($pos + 1 >= $len) {
                    throw new EscapeException('Incomplete escape sequence at end of string');
                }

                $escape = substr($content, $pos, 2);
                if (!in_array($escape, self::VALID_ESCAPES, true)) {
                    throw new EscapeException("Invalid escape sequence: {$escape}");
                }

                $pos += 2;
            } else {
                $pos++;
            }
        }
    }
}
