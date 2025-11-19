<?php

declare(strict_types=1);

namespace Toon\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Toon\Toon;
use Toon\EncodeOptions;
use Toon\DecodeOptions;

final class HelperFunctionsTest extends TestCase
{
    private string $tempFile;

    protected function setUp(): void
    {
        $this->tempFile = sys_get_temp_dir() . '/toon_test_' . uniqid() . '.toon';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempFile)) {
            unlink($this->tempFile);
        }
    }

    public function test_encode_to_file_writes_toon_content(): void
    {
        $data = ['name' => 'Alice', 'age' => 30];

        Toon::encodeToFile($data, $this->tempFile);

        $this->assertFileExists($this->tempFile);
        $content = file_get_contents($this->tempFile);
        $this->assertIsString($content);
        $this->assertStringContainsString('name: Alice', $content);
        $this->assertStringContainsString('age: 30', $content);
    }

    public function test_encode_to_file_with_options(): void
    {
        $data = ['items' => [1, 2, 3]];
        $options = new EncodeOptions(maxCompactArrayLength: 5);

        Toon::encodeToFile($data, $this->tempFile, $options);

        $content = file_get_contents($this->tempFile);
        $this->assertIsString($content);
        $this->assertStringContainsString('items[3]: 1,2,3', $content);
    }

    public function test_decode_from_file_reads_toon_content(): void
    {
        $toon = "name: Alice\nage: 30";
        file_put_contents($this->tempFile, $toon);

        $result = Toon::decodeFromFile($this->tempFile);

        $this->assertSame(['name' => 'Alice', 'age' => 30], $result);
    }

    public function test_decode_from_file_with_options(): void
    {
        // Test with permissive mode (key order preservation can vary)
        $toon = "name: Bob\nage: 25";
        file_put_contents($this->tempFile, $toon);
        $options = new DecodeOptions(strict: false, preserveKeyOrder: false);

        $result = Toon::decodeFromFile($this->tempFile, $options);

        $this->assertSame(['name' => 'Bob', 'age' => 25], $result);
    }

    public function test_decode_from_file_throws_when_file_not_found(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('File not found');

        Toon::decodeFromFile('/nonexistent/file.toon');
    }

    public function test_validate_returns_success_for_valid_toon(): void
    {
        $toon = "name: Alice\nage: 30";

        $result = Toon::validate($toon);

        $this->assertTrue($result->isValid());
        $this->assertNull($result->getError());
    }

    public function test_validate_returns_error_for_invalid_toon(): void
    {
        $toon = 'bad: "invalid\xescape"';  // Invalid escape sequence

        $result = Toon::validate($toon);

        $this->assertFalse($result->isValid());
        $this->assertNotNull($result->getError());
    }

    public function test_validate_with_options(): void
    {
        $toon = "name: Alice\nage: 30";
        $options = new DecodeOptions(strict: true);

        $result = Toon::validate($toon, $options);

        $this->assertTrue($result->isValid());
    }

    public function test_encode_to_file_throws_on_write_failure(): void
    {
        $data = ['name' => 'Alice'];
        $invalidPath = '/nonexistent/directory/file.toon';

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to write to file');

        Toon::encodeToFile($data, $invalidPath);
    }
}
