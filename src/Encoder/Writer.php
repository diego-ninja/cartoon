<?php

// ABOUTME: Converts PHP values to TOON format.
// ABOUTME: Analyzes structure, applies formatting rules, handles escaping.

declare(strict_types=1);

namespace Toon\Encoder;

use Toon\EncodeOptions;
use Toon\Enum\IndentationType;
use Toon\Exception\CircularReferenceException;
use Toon\Exception\UnencodableException;

final class Writer
{
    /** * Tracks visited objects to prevent circular references.
     * @var array<int, bool>
     */
    private array $visiting = [];

    public function __construct(
        private readonly EncodeOptions $options,
    ) {}

    /**
     * @throws UnencodableException
     * @throws CircularReferenceException
     */
    public function write(mixed $data): string
    {
        $this->visiting = [];
        return $this->writeValue($data, 0);
    }

    /**
     * @throws CircularReferenceException
     * @throws UnencodableException
     */
    private function writeValue(mixed $value, int $depth): string
    {
        if ($value === null) {
            return 'null';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_int($value) || is_float($value)) {
            return $this->encodeNumber($value);
        }

        if (is_string($value)) {
            return $this->encodeString($value);
        }

        if (is_object($value)) {
            return $this->encodeObject($value, $depth);
        }

        if (is_array($value)) {
            return $this->encodeArray($value, $depth);
        }

        if (is_resource($value)) {
            throw new UnencodableException('Cannot encode resource');
        }

        throw new UnencodableException('Cannot encode value of type ' . get_debug_type($value));
    }

    /**
     * @throws UnencodableException
     */
    private function encodeNumber(int|float $num): string
    {
        if ($num === 0.0 || $num === -0.0) {
            return '0';
        }

        if (is_infinite($num) || is_nan($num)) {
            return 'null';
        }

        $str = (string) $num;

        // Scientific notation handling
        if (str_contains($str, 'e') || str_contains($str, 'E')) {
            $formatted = sprintf('%.14F', $num);
            $str = rtrim(rtrim($formatted, '0'), '.');
        }

        // Float cleanup
        if (str_contains($str, '.')) {
            $str = rtrim(rtrim($str, '0'), '.');
        }

        return $str;
    }

    /**
     * @throws UnencodableException
     */
    private function encodeString(string $str): string
    {
        if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $str) === 1) {
            throw new UnencodableException('String contains unencodable control characters');
        }

        if ($this->needsQuoting($str)) {
            return '"' . $this->escapeString($str) . '"';
        }
        return $str;
    }

    private function needsQuoting(string $str): bool
    {
        if ($str === '') {
            return true;
        }

        if (in_array($str, ['true', 'false', 'null'], true)) {
            return true;
        }

        // §7.2: Quote numeric-like strings
        if (preg_match('/^-?\d+(?:\.\d+)?(?:e[+-]?\d+)?$/i', $str) === 1) {
            return true;
        }
        if (preg_match('/^0\d+$/', $str) === 1) {
            return true;
        }

        if (preg_match('/[\s,:"\[\]{}|-]/', $str) === 1) {
            return true;
        }
        return false;
    }

    /**
     * @throws UnencodableException
     */
    private function escapeString(string $str): string
    {
        return str_replace(
            ['\\', '"', "\n", "\r", "\t"],
            ['\\\\', '\\"', '\\n', '\\r', '\\t'],
            $str,
        );
    }

    /**
     * @throws CircularReferenceException
     * @throws UnencodableException
     */
    private function encodeObject(object $obj, int $depth): string
    {
        if ($obj instanceof \Closure) {
            throw new UnencodableException('Cannot encode closure');
        }

        $id = spl_object_id($obj);
        if (isset($this->visiting[$id])) {
            throw new CircularReferenceException('Circular reference detected');
        }

        $this->visiting[$id] = true;

        try {
            if ($obj instanceof \BackedEnum) {
                return $this->writeValue($obj->value, $depth);
            }

            if ($obj instanceof \UnitEnum) {
                return $this->encodeString($obj->name);
            }

            $data = (array) $obj;
            if (empty($data)) {
                return '';
            }

            return $this->encodeArray($data, $depth);
        } finally {
            unset($this->visiting[$id]);
        }
    }

    /**
     * @param array<array-key, mixed> $arr
     * @throws CircularReferenceException
     * @throws UnencodableException
     */
    private function encodeArray(array $arr, int $depth): string
    {
        if (empty($arr)) {
            return '[0]:';
        }

        if (array_is_list($arr)) {
            return $this->encodeList($arr, $depth);
        }

        return $this->encodeArrayObject($arr, $depth);
    }

    /**
     * @param list<mixed> $list
     * @throws CircularReferenceException
     * @throws UnencodableException
     */
    private function encodeList(array $list, int $depth): string
    {
        $count = count($list);

        if ($count <= $this->options->maxCompactArrayLength && $this->allPrimitives($list)) {
            $values = array_map(
                fn(mixed $v): string => $this->writeValue($v, $depth + 1),
                $list,
            );
            $delimiter = $this->options->preferredDelimiter->value;
            return "[$count]: " . implode($delimiter, $values);
        }

        $lines = ["[$count]:"];
        foreach ($list as $item) {
            $value = $this->writeValue($item, $depth + 1);
            $lines[] = $this->indent($depth + 1) . '- ' . $value;
        }

        return implode("\n", $lines);
    }

    // -------------------------------------------------------------------------
    // ARRAY OBJECT LOGIC
    // -------------------------------------------------------------------------

    /**
     * @param array<array-key, mixed> $obj
     * @throws UnencodableException
     * @throws CircularReferenceException
     */
    private function encodeArrayObject(array $obj, int $depth): string
    {
        $lines = [];

        foreach ($obj as $key => $value) {
            if (is_array($value) && array_is_list($value)) {
                $lines[] = $this->encodeListProperty($key, $value, $depth);
            } else {
                $lines[] = $this->encodeObjectProperty($key, $value, $depth);
            }
        }

        return implode("\n", $lines);
    }

    /**
     * @param string|int $key
     * @param list<mixed> $list
     * @param int $depth
     * @return string
     * @throws CircularReferenceException
     * @throws UnencodableException
     */
    private function encodeListProperty(string|int $key, array $list, int $depth): string
    {
        $count = count($list);
        $keyStr = $this->encodeKey($key);

        // 1. Compact List
        if ($count <= $this->options->maxCompactArrayLength && $this->allPrimitives($list)) {
            return $this->formatCompactList($keyStr, $list, $count, $depth);
        }

        // 2. Table List
        $tableKeys = $this->getTableKeys($list);
        if ($tableKeys !== null) {
            return $this->formatTableList($keyStr, $list, $tableKeys, $count, $depth);
        }

        // 3. Expanded List (Fallback)
        return $this->formatExpandedList($keyStr, $list, $count, $depth);
    }

    /**
     * @param list<mixed> $list
     * @throws UnencodableException
     * @throws CircularReferenceException
     */
    private function formatCompactList(string $keyStr, array $list, int $count, int $depth): string
    {
        $mappedValues = array_map(
            fn(mixed $v): string => $this->writeValue($v, $depth + 1),
            $list,
        );
        $delimiter = $this->options->preferredDelimiter->value;

        return $this->indent($depth) . $keyStr . "[$count]:" . ($count > 0 ? ' ' : '') . implode($delimiter, $mappedValues);
    }

    /**
     * @param list<mixed> $list
     * @param list<string|int> $columns
     * @throws UnencodableException
     * @throws CircularReferenceException
     */
    private function formatTableList(string $keyStr, array $list, array $columns, int $count, int $depth): string
    {
        $headerStr = implode(',', $columns);
        $lines = [];

        // Header: key[N]{col1,col2}:
        $lines[] = $this->indent($depth) . $keyStr . "[$count]{" . $headerStr . "}:";

        foreach ($list as $row) {
            assert(is_array($row));

            $rowValues = array_map(
                fn(mixed $v): string => $this->writeValue($v, $depth + 1),
                $row,
            );

            // Rows: val1,val2 (NO braces)
            $lines[] = $this->indent($depth + 1) . implode(',', $rowValues);
        }

        return implode("\n", $lines);
    }

    /**
     * @param list<mixed> $list
     * @throws UnencodableException
     * @throws CircularReferenceException
     */
    private function formatExpandedList(string $keyStr, array $list, int $count, int $depth): string
    {
        $lines = [];
        $lines[] = $this->indent($depth) . $keyStr . "[$count]:";

        foreach ($list as $item) {
            $itemStr = $this->writeValue($item, $depth + 2);

            // If a list-like array renders as a single line (e.g. compact list), it must be quoted
            // to be treated as a string literal within the expanded list.
            if (is_array($item) && array_is_list($item) && !str_contains($itemStr, "\n")) {
                $itemStr = '"' . $this->escapeString($itemStr) . '"';
            }

            $itemLines = explode("\n", $itemStr);

            $firstLineContent = ltrim($itemLines[0]);

            $line = $this->indent($depth + 1) . '-';
            if ($firstLineContent !== '' || count($itemLines) > 1) {
                $line .= ' ' . $firstLineContent;
            }
            $lines[] = $line;

            $lineCount = count($itemLines);
            for ($i = 1; $i < $lineCount; $i++) {
                $lines[] = $itemLines[$i];
            }
        }

        return implode("\n", $lines);
    }

    /**
     * @throws UnencodableException
     * @throws CircularReferenceException
     */
    private function encodeObjectProperty(string|int $key, mixed $value, int $depth): string
    {
        $keyStr = $this->encodeKey($key);
        $valueStr = $this->writeValue($value, $depth + 1);

        $isNested = (is_array($value) || is_object($value)) && !$value instanceof \UnitEnum;
        $isMultiline = str_contains($valueStr, "\n");

        if ($isNested || $isMultiline) {
            // For nested/multiline values, use a newline, but not if the value is empty (e.g. empty object)
            return $this->indent($depth) . $keyStr . ':' . ($valueStr === '' ? '' : "\n" . $valueStr);
        }

        // For simple primitives, use a space
        return $this->indent($depth) . $keyStr . ': ' . $valueStr;
    }

    // -------------------------------------------------------------------------
    // HELPERS
    // -------------------------------------------------------------------------

    /**
     * @throws UnencodableException
     */
    private function encodeKey(string|int $key): string
    {
        $keyStr = (string) $key;
        if (preg_match('/^[A-Za-z_][A-Za-z0-9_.]*$/', $keyStr) !== 1) {
            return '"' . $this->escapeString($keyStr) . '"';
        }
        return $keyStr;
    }

    /**
     * @param list<mixed> $list
     * @return list<string|int>|null
     */
    private function getTableKeys(array $list): ?array
    {
        if (empty($list)) {
            return null;
        }

        $first = $list[0];
        if (!is_array($first) || array_is_list($first)) {
            return null;
        }

        $expectedKeys = array_keys($first);

        foreach ($list as $item) {
            if (!is_array($item)) {
                return null;
            }
            if (array_keys($item) !== $expectedKeys) {
                return null;
            }
            if (!$this->allPrimitives($item)) {
                return null;
            }
        }

        return $expectedKeys;
    }

    /**
     * @param array<array-key, mixed> $arr
     */
    private function allPrimitives(array $arr): bool
    {
        return array_all($arr, fn(mixed $value): bool => !is_array($value) && !is_object($value));
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
