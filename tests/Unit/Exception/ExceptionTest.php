<?php

declare(strict_types=1);

namespace Toon\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use Toon\Exception\CircularReferenceException;
use Toon\Exception\EscapeException;
use Toon\Exception\SyntaxException;
use Toon\Exception\ToonException;
use Toon\Exception\UnencodableException;
use Toon\Exception\ValidationException;

final class ExceptionTest extends TestCase
{
    public function test_all_exceptions_extend_base_exception(): void
    {
        $exceptions = [
            new SyntaxException('test'),
            new ValidationException('test'),
            new EscapeException('test'),
            new UnencodableException('test'),
            new CircularReferenceException('test'),
        ];

        foreach ($exceptions as $exception) {
            $this->assertInstanceOf(ToonException::class, $exception);
        }
    }

    public function test_syntax_exception_includes_context(): void
    {
        $exception = new SyntaxException('Invalid syntax', lineNumber: 5, column: 10, snippet: 'bad line');

        $this->assertSame(5, $exception->getLineNumber());
        $this->assertSame(10, $exception->getColumn());
        $this->assertSame('bad line', $exception->getSnippet());
    }

    public function test_unencodable_exception_includes_path(): void
    {
        $exception = new UnencodableException('Cannot encode', path: 'users.0.name');

        $this->assertSame('users.0.name', $exception->getPath());
    }
}
