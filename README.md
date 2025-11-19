# Cartoon- Token-Oriented Object Notation for PHP

High-performance PHP 8.4+ library for encoding and decoding TOON (Token-Oriented Object Notation) format with full spec compliance.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/diego-ninja/cartoon.svg?style=flat-square&color=blue&logoColor=%23949ca4&labelColor=%233f4750)](https://packagist.org/packages/diego-ninja/cartoon)
[![Total Downloads](https://img.shields.io/packagist/dt/diego-ninja/cartoon.svg?style=flat-square&color=blue&logoColor=%23949ca4&labelColor=%233f4750)](https://packagist.org/packages/diego-ninja/cartoon)
![PHP Version](https://img.shields.io/packagist/php-v/diego-ninja/cartoon.svg?style=flat-square&color=blue&logoColor=%23949ca4&labelColor=%233f4750)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square&color=blue&logoColor=%23949ca4&labelColor=%233f4750)](https://opensource.org/licenses/MIT)
![GitHub last commit](https://img.shields.io/github/last-commit/diego-ninja/cartoon?style=flat-square&color=blue&logoColor=%23949ca4&labelColor=%233f4750)
[![wakatime](https://wakatime.com/badge/user/bd65f055-c9f3-4f73-92aa-3c9810f70cc3/project/3cc2ec60-a8b4-4ddc-aeac-ea78e37a094b.svg?style=flat-square&color=blue&logoColor=%23949ca4&labelColor=%233f4750)](https://wakatime.com/badge/user/bd65f055-c9f3-4f73-92aa-3c9810f70cc3/project/3cc2ec60-a8b4-4ddc-aeac-ea78e37a094b)

[![Tests](https://img.shields.io/github/actions/workflow/status/diego-ninja/cartoon/tests.yml?branch=main&style=flat-square&logo=github&label=tests&logoColor=%23949ca4&labelColor=%233f4750)]()
[![Static Analysis](https://img.shields.io/github/actions/workflow/status/diego-ninja/cartoon/static-analysis.yml?branch=main&style=flat-square&logo=github&label=phpstan%2010&logoColor=%23949ca4&labelColor=%233f4750)]()
[![Code Style](https://img.shields.io/github/actions/workflow/status/diego-ninja/cartoon/code-style.yml?branch=main&style=flat-square&logo=github&label=pint%3A%20PER&logoColor=%23949ca4&labelColor=%233f4750)]()
[![Coveralls](https://img.shields.io/coverallsCoverage/github/diego-ninja/cartoon?branch=main&style=flat-square&logo=coveralls&logoColor=%23949ca4&labelColor=%233f4750&link=https%3A%2F%2Fcoveralls.io%2Fgithub%2Fdiego-ninja%2Fcartoon)]()

## Features

- 🚀 **High Performance**: AST-based parser with JIT optimization
- 🎯 **Spec Compliant**: Full adherence to [TOON specification](https://github.com/toon-format/spec)
- 🔒 **Type Safe**: PHPStan level 10, strict types throughout
- 🧪 **Well Tested**: 95%+ code coverage
- 🎨 **Modern PHP**: Leverages PHP 8.4+ features (readonly, enums, union types)
- 📦 **Zero Dependencies**: No runtime dependencies

## Installation

```bash
composer require toon/toon
```

**Requirements:** PHP 8.4 or higher

## Quick Start

### Decoding TOON to PHP

```php
use Ninja\Cartoon\Toon;

$toon = <<<TOON
name: Alice
age: 30
active: true
TOON;

$data = Toon::decode($toon);
// ['name' => 'Alice', 'age' => 30, 'active' => true]
```

### Encoding PHP to TOON

```php
use Ninja\Cartoon\Toon;

$data = [
    'name' => 'Bob',
    'age' => 25,
    'tags' => ['php', 'toon', 'awesome'],
];

$toon = Toon::encode($data);
```

Output:
```
name: Bob
age: 25
tags[3]: php,toon,awesome
```

## Advanced Usage

### Custom Encoding Options

```php
use Ninja\Cartoon\{EncodeOptions,Enum\DelimiterType,Enum\IndentationType,Toon};

$options = new EncodeOptions(
    preferredDelimiter: DelimiterType::Tab,
    indentSize: 4,
    indentationType: IndentationType::Tabs,
    maxCompactArrayLength: 20,
);

$toon = Toon::encode($data, $options);
```

### Custom Decoding Options

```php
use Ninja\Cartoon\{Toon, DecodeOptions};

$options = new DecodeOptions(
    strict: false,              // Allow non-canonical input
    preserveKeyOrder: true,     // Maintain key order
);

$data = Toon::decode($toon, $options);
```

### Strict vs Permissive Mode

**Strict mode (default):**
- Validates array lengths exactly
- Requires canonical number format
- Enforces consistent indentation

**Permissive mode:**
- Tolerates array length mismatches
- Accepts non-canonical numbers
- Allows mixed indentation

## TOON Format Overview

TOON is a line-oriented, indentation-based format encoding the JSON data model:

**Objects:**
```toon
name: Alice
age: 30
```

**Arrays:**
```toon
items[3]: a,b,c
```

**Nested structures:**
```toon
user:
  name: Bob
  address:
    city: NYC
```

**Tabular data:**
```toon
users[2]{id,name}:
1,Alice
2,Bob
```

See the [official spec](https://github.com/toon-format/spec/blob/main/SPEC.md) for complete details.

## Development

### Setup

```bash
git clone https://github.com/your-username/toon.git
cd toon
composer install
```

### Running Tests

```bash
# All tests
vendor/bin/phpunit

# With coverage
vendor/bin/phpunit --coverage-html coverage

# Specific test suite
vendor/bin/phpunit tests/Unit
vendor/bin/phpunit tests/Integration
```

### Code Quality

```bash
# PHPStan (level 10)
vendor/bin/phpstan analyse

# PHP-CS-Fixer (PER coding style)
vendor/bin/php-cs-fixer fix

# All checks
composer test
composer analyze
composer fix
```

## Performance

Benchmarks on PHP 8.4 with JIT enabled:

- **Decode**: ~1000 documents/sec (1KB each)
- **Encode**: ~1500 documents/sec (1KB each)
- **Memory**: < 2MB for 10K element arrays

Run benchmarks: `vendor/bin/phpunit tests/Benchmark`

## Error Handling

The library provides specific exceptions for different error cases:

- `SyntaxException`: Invalid TOON syntax
- `ValidationException`: Spec violations in strict mode
- `EscapeException`: Invalid escape sequences
- `UnencodableException`: PHP value cannot be encoded (resources, INF, NAN)
- `CircularReferenceException`: Circular reference detected

All exceptions extend `ToonException` for easy catching.

## Limitations

- **Multi-line strings**: Not supported (TOON spec doesn't allow them)
- **Special floats**: INF, -INF, NAN cannot be encoded
- **Control characters**: Only `\n`, `\r`, `\t` can be escaped
- **Resources**: Cannot be encoded
- **Closures**: Cannot be encoded

## Contributing

Contributions welcome! Please:

1. Follow PER coding style
2. Add tests for new features
3. Ensure PHPStan level 10 passes
4. Update documentation as needed

## License

MIT License. See [LICENSE](LICENSE) file.

## Links

- [TOON Specification](https://github.com/toon-format/spec)
- [Issue Tracker](https://github.com/diego-ninja/cartoon/issues)
- [Changelog](CHANGELOG.md)
