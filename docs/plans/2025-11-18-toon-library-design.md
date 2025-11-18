# TOON Library Design Document

**Date:** 2025-11-18
**Author:** Claude & Diego
**Status:** Approved

## Overview

High-performance PHP 8.4+ library for encoding and decoding TOON (Token-Oriented Object Notation) format. TOON is a line-oriented, indentation-based text format encoding the JSON data model.

**Spec Reference:** https://github.com/toon-format/spec/blob/main/SPEC.md

## Use Cases

1. **Interoperability:** Work with systems that use TOON format
2. **Human-Readable Configuration:** Generate and parse configuration files
3. **Data Serialization:** Alternative to JSON for data exchange

**Priority:** Performance is critical across all use cases.

## Architecture

### Core Philosophy

- **AST-based parsing** with typed classes leveraging PHP 8.4+ features
- **Zero runtime dependencies** (stdlib only)
- **Strict type safety** for JIT optimization
- **Modular design** with clear separation of concerns

### Component Structure

```
Toon\
├── Toon (facade)
├── EncodeOptions
├── DecodeOptions
├── Decoder\
│   ├── Tokenizer
│   ├── Parser
│   └── Token
├── Encoder\
│   └── Writer
├── AST\
│   ├── Node (interface)
│   ├── ObjectNode
│   ├── ArrayNode
│   ├── PrimitiveNode
│   ├── NodeType (enum)
│   └── DelimiterType (enum)
└── Exception\
    ├── ToonException
    ├── SyntaxException
    ├── ValidationException
    ├── EscapeException
    ├── UnencodableException
    └── CircularReferenceException
```

## Public API

### Facade

```php
namespace Toon;

final readonly class Toon {
    public static function encode(mixed $data, ?EncodeOptions $options = null): string;
    public static function decode(string $toon, ?DecodeOptions $options = null): mixed;
}
```

### Options Classes

**EncodeOptions:**
```php
final readonly class EncodeOptions {
    public function __construct(
        public DelimiterType $preferredDelimiter = DelimiterType::Comma,
        public int $indentSize = 2,
        public IndentationType $indentationType = IndentationType::Spaces,
        public bool $prettyArrays = true,
        public int $maxCompactArrayLength = 10,
    ) {}
}
```

**DecodeOptions:**
```php
final readonly class DecodeOptions {
    public function __construct(
        public bool $strict = true,
        public bool $preserveKeyOrder = true,
    ) {}
}
```

### Enums

```php
enum DelimiterType: string {
    case Comma = ',';
    case Tab = "\t";
    case Pipe = '|';
}

enum IndentationType {
    case Spaces;
    case Tabs;
}
```

## Data Flow

### Decoding: TOON → PHP

**Three-step pipeline:**

1. **Tokenization** (`Tokenizer`)
   - Read TOON string line by line
   - Generate token stream with type, value, indent level, line number
   - Detect delimiters in array headers
   - Validate basic syntax

2. **Parsing** (`Parser`)
   - Consume tokens, build AST
   - Stack-based tracking for indentation
   - Validate structure (array lengths, consistent indentation in strict mode)
   - Create typed AST nodes

3. **Conversion** (AST → PHP)
   - Each node implements `toPhp(): mixed`
   - ObjectNode → associative array
   - ArrayNode → indexed array
   - PrimitiveNode → scalar values

### Encoding: PHP → TOON

**Two-step pipeline:**

1. **Analysis** (`Writer`)
   - Recursively analyze PHP structure
   - Detect types: arrays vs objects vs primitives
   - Determine array format (compact/tabular/expanded)
   - Calculate indentation depth

2. **Writing** (Buffer-based generation)
   - Pre-allocate buffer (~1.5x JSON size as heuristic)
   - Generate TOON line by line
   - Apply canonical encoding rules
   - Handle escaping and quoting

## Critical Implementation Details

### Root Form Detection

First non-empty line determines root type:
- Array header (`[N]:` or `[N]{fields}:`) → root array
- Single line without colon or bracket → primitive
- Otherwise → object
- Empty document → empty object

```php
private function detectRootType(string $firstLine): RootType {
    if (preg_match('/^\[.*\]:/', $firstLine)) return RootType::Array;
    if (!str_contains($firstLine, ':') && !str_contains($firstLine, '[')) {
        return RootType::Primitive;
    }
    return RootType::Object;
}
```

### Canonical Number Encoding

Spec requirements:
- No exponent notation (1e6 → 1000000)
- No leading zeros (except single "0")
- No trailing fractional zeros
- Normalize -0 to 0

```php
private function encodeNumber(int|float $num): string {
    if ($num === 0.0 || $num === -0.0) return '0';
    $str = (string) $num;
    if (str_contains($str, 'e') || str_contains($str, 'E')) {
        $str = number_format($num, decimals: 14, thousands_separator: '');
    }
    return rtrim(rtrim($str, '0'), '.');
}
```

### Tabular Array Detection

Array is tabular if all elements are objects with identical key sets:

```php
private function isTabular(array $items): bool {
    if (empty($items)) return false;
    $firstKeys = null;
    foreach ($items as $item) {
        if (!is_array($item) || array_is_list($item)) return false;
        $keys = array_keys($item);
        sort($keys);
        if ($firstKeys === null) {
            $firstKeys = $keys;
        } elseif ($firstKeys !== $keys) {
            return false;
        }
    }
    return true;
}
```

### Circular Reference Detection

Track visiting objects during encoding:

```php
private array $visiting = [];

private function encodeValue(mixed $value): string {
    if (is_object($value)) {
        $id = spl_object_id($value);
        if (isset($this->visiting[$id])) {
            throw new CircularReferenceException("Circular reference detected");
        }
        $this->visiting[$id] = true;
        try {
            return $this->encodeObject($value);
        } finally {
            unset($this->visiting[$id]);
        }
    }
    // ...
}
```

## Error Handling

### Exception Hierarchy

All exceptions extend `ToonException`:

**Decode Errors:**
- `SyntaxException`: Syntax errors (unbalanced brackets, missing colons, invalid indentation)
- `ValidationException`: Spec violations in strict mode (array length mismatch, inconsistent delimiter)
- `EscapeException`: Invalid escape sequences (only `\\`, `\"`, `\n`, `\r`, `\t` allowed)

**Encode Errors:**
- `UnencodableException`: PHP data that cannot be encoded (resources, closures, objects without __serialize)
- `CircularReferenceException`: Circular reference detected

**Exception Context:**
- Descriptive English message
- `lineNumber`, `column`, `snippet` for decode errors
- `path` in structure for encode errors (e.g., "users.0.address.street")

### Modes

**Strict Mode (default):**
- All validations active
- Array length must match exactly
- Canonical number format required
- Consistent indentation enforced

**Permissive Mode:**
- Tolerates non-canonical numbers in input
- Flexible indentation (mixing spaces/tabs)
- Approximate array length
- Escape errors always fail (spec is strict on this)

**Note:** Encoder always generates canonical TOON regardless of mode.

## Edge Cases

### Strings
- Numbers as strings: `"123"` stays string
- Whitespace-only: `"   "` requires quotes
- Empty string: `""`
- Multi-line strings: Not supported by spec → `UnencodableException`

### Special Values
- Empty arrays: `items[0]:`
- Empty objects: Empty document or nothing
- `INF`, `-INF`, `NAN`: `UnencodableException` (JSON incompatible)

### Keys
- Duplicate keys in objects:
  - Strict mode: `ValidationException`
  - Permissive mode: last wins
- Valid unquoted key pattern: `^[A-Za-z_][A-Za-z0-9_.]*$`

### Unicode
- UTF-8 everywhere, no BOM
- Unicode strings pass through unmodified
- Control characters (except \n, \r, \t):
  - Cannot be escaped (only 5 escapes allowed)
  - `UnencodableException` on encode
  - `SyntaxException` on decode

## PHP Enum Support

**Encoding (PHP → TOON):**
- Backed enums: encode as backing value
- Unit enums: encode as case name (string)

**Decoding (TOON → PHP):**
- Returns primitive values (string/int)
- No automatic enum reconstruction
- User can hydrate enums post-decode
- Future consideration: optional type mapping

## Performance Optimizations

### String Building
- Pre-allocate buffer (estimated 1.5x JSON size)
- Minimize string concatenations
- Use `substr_replace` for in-place buffer writing

### Tokenizer
- Compile regexes once in constructor
- Process full lines (not char-by-char)
- Early validation during tokenization

### Parser
- Single-pass with minimal stack
- Immediate AST node creation
- No backtracking

### JIT Optimization
- Typed properties everywhere
- Mark hot-path methods as `#[Pure]` when applicable
- Prefer `match` over `switch`
- Avoid `mixed` in hot paths

### Memory Efficiency
- Process large files (>10MB) in chunks
- Optional streaming: `Toon::decodeStream(resource): Generator`
- Release AST nodes immediately after writing

## Testing Strategy

### Test Organization
- **Unit tests:** Each component (Tokenizer, Parser, Writer, AST nodes)
- **Integration tests:** Round-trip verification (PHP → TOON → PHP must be identical)
- **Spec compliance tests:** Cases from official specification
- **Performance benchmarks:** Large datasets (10k+ element arrays, deeply nested objects)

### Coverage Requirements
- 95%+ coverage in hot paths (Tokenizer, Parser, Writer)
- 100% coverage for edge cases (escaping, canonical numbers, delimiters)

### Property-Based Testing
- Generate random PHP structures
- Verify round-trip preserves data
- Test encoding always produces valid TOON

## Project Structure

```
toon/
├── src/
│   ├── Toon.php
│   ├── EncodeOptions.php
│   ├── DecodeOptions.php
│   ├── Decoder/
│   │   ├── Tokenizer.php
│   │   ├── Parser.php
│   │   └── Token.php
│   ├── Encoder/
│   │   └── Writer.php
│   ├── AST/
│   │   ├── Node.php
│   │   ├── ObjectNode.php
│   │   ├── ArrayNode.php
│   │   ├── PrimitiveNode.php
│   │   ├── NodeType.php
│   │   └── DelimiterType.php
│   └── Exception/
│       ├── ToonException.php
│       ├── SyntaxException.php
│       ├── ValidationException.php
│       ├── EscapeException.php
│       ├── UnencodableException.php
│       └── CircularReferenceException.php
├── tests/
│   ├── Unit/
│   ├── Integration/
│   └── Benchmark/
├── docs/
│   └── plans/
├── composer.json
├── phpunit.xml
├── .php-cs-fixer.php
└── README.md
```

## Dependencies

**Runtime:**
- PHP 8.4+ (strict requirement)
- Zero external dependencies

**Development:**
- PHPUnit 11+
- PHPStan 2.0+ (level 10)
- PHPStan Strict Rules
- PHP-CS-Fixer 3.0+ (PER coding style)

## Quality Gates

### CI Pipeline
- PHPStan level 10: `vendor/bin/phpstan analyse src --level=10`
- PHPUnit with 95% minimum coverage
- PHP-CS-Fixer: PER (PHP Evolving Recommendation) style
- Performance regression tests: must stay within X% of baseline

### Git Hooks (pre-commit)
- Run PHPStan
- Run tests
- Run CS-Fixer
- Block commit on failure

## Versioning

### Semantic Versioning
- **1.0.0:** First stable release with full spec compliance
- **Patch (1.0.x):** Bug fixes, performance improvements
- **Minor (1.x.0):** New optional features (e.g., streaming support)
- **Major (x.0.0):** Breaking changes to public API

### Compatibility
- PHP 8.4+ requirement is permanent
- TOON spec compliance: strict adherence to official spec
- Spec changes require major version bump if breaking

### CHANGELOG.md
- Detailed change log for all releases
- Categories: Added, Changed, Deprecated, Removed, Fixed, Security

## Documentation

### README.md

**Installation:**
```bash
composer require toon/toon
```

**Basic Usage:**
```php
use Toon\Toon;

// Encoding
$data = ['users' => [
    ['id' => 1, 'name' => 'Alice'],
    ['id' => 2, 'name' => 'Bob']
]];
$toon = Toon::encode($data);

// Decoding
$decoded = Toon::decode($toon);
```

**Advanced Configuration:**

```php
use Toon\{DecodeOptions,EncodeOptions,Enum\DelimiterType,Enum\IndentationType,Toon};

$encoded = Toon::encode($data, new EncodeOptions(
    preferredDelimiter: DelimiterType::Tab,
    indentSize: 4,
    indentationType: IndentationType::Tabs,
    prettyArrays: true,
    maxCompactArrayLength: 20
));

$decoded = Toon::decode($toon, new DecodeOptions(
    strict: false,
    preserveKeyOrder: true
));
```

### Code Documentation
- Complete docblocks for all public API methods
- `@param`, `@return`, `@throws` annotations
- Usage examples in docblocks where helpful
- `@internal` for implementation classes

## Implementation Notes

### Regex Patterns (compiled once)
- Array headers: `/^\s*\[(\d+)([,\t|])?\](\{[^}]+\})?:/`
- Object keys: `/^\s*([A-Za-z_][A-Za-z0-9_.]*|"(?:[^"\\]|\\.)*")\s*:/`
- List items: `/^\s*-\s+(.+)$/`

### String Quoting Rules (Encoding)
Quote strings that contain:
- Whitespace
- Delimiters (comma, tab, pipe)
- Colons
- Special characters
- Match reserved keywords (true, false, null)

### Array Length Validation (Strict Mode)
```php
if ($options->strict && $actualCount !== $declaredCount) {
    throw new ValidationException(
        "Array length mismatch: declared $declaredCount, found $actualCount",
        lineNumber: $node->headerLine
    );
}
```

## Future Considerations

### Possible Features (Post 1.0)
- Streaming API for large files
- Schema validation with type mapping
- Enum reconstruction on decode
- Custom serialization handlers
- Performance profiling tools

### Non-Goals
- Support for PHP < 8.4
- Runtime configuration beyond Options classes
- Plugin system
- Custom data types beyond JSON model
