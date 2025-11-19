<?php

declare(strict_types=1);

namespace Ninja\Cartoon\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use Ninja\Cartoon\Exception\CircularReferenceException;
use Ninja\Cartoon\Exception\EscapeException;
use Ninja\Cartoon\Exception\SyntaxException;
use Ninja\Cartoon\Exception\ToonException;
use Ninja\Cartoon\Exception\UnencodableException;
use Ninja\Cartoon\Exception\ValidationException;

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

    public function test_validation_exception_includes_line_number(): void
    {
        $exception = new ValidationException('Invalid data', lineNumber: 42);

        $this->assertSame(42, $exception->getLineNumber());
    }
}
