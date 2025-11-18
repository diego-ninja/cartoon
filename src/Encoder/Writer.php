<?php

// ABOUTME: Converts PHP values to TOON format.
// ABOUTME: Analyzes structure, applies formatting rules, handles escaping.

declare(strict_types=1);

namespace Toon\Encoder;

use Toon\EncodeOptions;
use Toon\Exception\CircularReferenceException;
use Toon\Exception\UnencodableException;
use Toon\IndentationType;

final class Writer
{
    /** @var array<int, bool> */
    private array $visiting = [];

    public function __construct(
        private readonly EncodeOptions $options,
    ) {
    }

    public function write(mixed $data): string
    {
        $this->visiting = [];
        return $this->writeValue($data, 0);
    }

    private function writeValue(mixed $value, int $depth): string
    {
        // Handle null
        if ($value === null) {
            return 'null';
        }

        // Handle booleans
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        // Handle numbers
        if (is_int($value) || is_float($value)) {
            return $this->encodeNumber($value);
        }

        // Handle strings
        if (is_string($value)) {
            return $this->encodeString($value);
        }

        // Handle objects
        if (is_object($value)) {
            return $this->encodeObject($value, $depth);
        }

        // Handle arrays
        if (is_array($value)) {
            return $this->encodeArray($value, $depth);
        }

        // Handle resources
        if (is_resource($value)) {
            throw new UnencodableException('Cannot encode resource');
        }

        throw new UnencodableException('Cannot encode value of type ' . get_debug_type($value));
    }

    private function encodeNumber(int|float $num): string
    {
        // Canonical number format
        if ($num === 0.0 || $num === -0.0) {
            return '0';
        }

        if (is_infinite($num) || is_nan($num)) {
            throw new UnencodableException('Cannot encode INF or NAN');
        }

        $str = (string) $num;

        // Convert scientific notation to decimal
        if (str_contains($str, 'e') || str_contains($str, 'E')) {
            $formatted = sprintf('%.14F', $num);
            $str = rtrim(rtrim($formatted, '0'), '.');
        }

        // Remove trailing zeros from decimals
        if (str_contains($str, '.')) {
            $str = rtrim(rtrim($str, '0'), '.');
        }

        return $str;
    }

    private function encodeString(string $str): string
    {
        // Check if quoting is needed
        if ($this->needsQuoting($str)) {
            return '"' . $this->escapeString($str) . '"';
        }

        return $str;
    }

    private function needsQuoting(string $str): bool
    {
        // Empty string needs quotes
        if ($str === '') {
            return true;
        }

        // Reserved keywords need quotes
        if (in_array($str, ['true', 'false', 'null'], true)) {
            return true;
        }

        // Check for whitespace, delimiters, special chars
        if (preg_match('/[\s,:"\[\]{}|]/', $str)) {
            return true;
        }

        return false;
    }

    private function escapeString(string $str): string
    {
        $escaped = $str;
        $escaped = str_replace('\\', '\\\\', $escaped);
        $escaped = str_replace('"', '\\"', $escaped);
        $escaped = str_replace("\n", '\\n', $escaped);
        $escaped = str_replace("\r", '\\r', $escaped);
        $escaped = str_replace("\t", '\\t', $escaped);

        // Check for unencodable control characters
        if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $escaped)) {
            throw new UnencodableException('String contains unencodable control characters');
        }

        return $escaped;
    }

    private function encodeObject(object $obj, int $depth): string
    {
        // Track circular references
        $id = spl_object_id($obj);
        if (isset($this->visiting[$id])) {
            throw new CircularReferenceException('Circular reference detected');
        }

        $this->visiting[$id] = true;

        try {
            // Convert object to array
            if ($obj instanceof \BackedEnum) {
                return $this->encodeString((string) $obj->value);
            }

            if ($obj instanceof \UnitEnum) {
                return $this->encodeString($obj->name);
            }

            // Try to serialize
            $data = (array) $obj;
            return $this->encodeArray($data, $depth);
        } finally {
            unset($this->visiting[$id]);
        }
    }

    /**
     * @param array<mixed> $arr
     */
    private function encodeArray(array $arr, int $depth): string
    {
        if (empty($arr)) {
            return '[0]:';
        }

        // Check if it's a list (indexed array)
        if (array_is_list($arr)) {
            return $this->encodeList($arr, $depth);
        }

        // It's an associative array (object)
        return $this->encodeObject_($arr, $depth);
    }

    /**
     * @param array<mixed> $list
     */
    private function encodeList(array $list, int $depth): string
    {
        $count = count($list);

        // Compact format for small primitive arrays
        if ($count <= $this->options->maxCompactArrayLength && $this->allPrimitives($list)) {
            $values = array_map(fn($v) => $this->writeValue($v, $depth + 1), $list);
            $delimiter = $this->options->preferredDelimiter->value;
            return "[{$count}]: " . implode($delimiter, $values);
        }

        // Expanded format
        $lines = ["[{$count}]:"];
        foreach ($list as $item) {
            $value = $this->writeValue($item, $depth + 1);
            $lines[] = $this->indent($depth + 1) . '- ' . $value;
        }

        return implode("\n", $lines);
    }

    /**
     * @param array<mixed> $obj
     */
    private function encodeObject_(array $obj, int $depth): string
    {
        $lines = [];

        foreach ($obj as $key => $value) {
            $keyStr = $this->encodeKey($key);
            $valueStr = $this->writeValue($value, $depth + 1);

            // Check if value is multiline
            if (str_contains($valueStr, "\n")) {
                $lines[] = $this->indent($depth) . $keyStr . ':';
                $lines[] = $valueStr;
            } else {
                $lines[] = $this->indent($depth) . $keyStr . ': ' . $valueStr;
            }
        }

        return implode("\n", $lines);
    }

    private function encodeKey(string|int $key): string
    {
        $keyStr = (string) $key;

        // Check if key needs quoting
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_.]*$/', $keyStr)) {
            return '"' . $this->escapeString($keyStr) . '"';
        }

        return $keyStr;
    }

    /**
     * @param array<mixed> $arr
     */
    private function allPrimitives(array $arr): bool
    {
        foreach ($arr as $value) {
            if (is_array($value) || is_object($value)) {
                return false;
            }
        }
        return true;
    }

    private function indent(int $depth): string
    {
        if ($depth === 0) {
            return '';
        }

        $char = $this->options->indentationType === IndentationType::Tabs ? "\t" : ' ';
        $size = $this->options->indentationType === IndentationType::Tabs ? 1 : $this->options->indentSize;

        return str_repeat($char, $depth * $size);
    }
}
