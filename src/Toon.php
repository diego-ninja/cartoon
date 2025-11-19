<?php

// ABOUTME: Main facade for encoding and decoding TOON format.
// ABOUTME: Provides simple static API for all TOON operations.

declare(strict_types=1);

namespace Ninja\Cartoon;

use Ninja\Cartoon\Decoder\Parser;
use Ninja\Cartoon\Decoder\Tokenizer;
use Ninja\Cartoon\Encoder\Writer;
use Ninja\Cartoon\Exception\CircularReferenceException;
use Ninja\Cartoon\Exception\UnencodableException;

final readonly class Toon
{
    /**
     * @throws CircularReferenceException
     * @throws UnencodableException
     */
    public static function encode(mixed $data, ?EncodeOptions $options = null): string
    {
        $options ??= new EncodeOptions();

        $writer = new Writer($options);
        return $writer->write($data);
    }

    /**
     * @param string $toon
     * @param DecodeOptions|null $options
     * @return mixed
     */
    public static function decode(string $toon, ?DecodeOptions $options = null): mixed
    {
        $options ??= new DecodeOptions();

        $tokenizer = new Tokenizer($options);
        $tokens = $tokenizer->tokenize($toon);

        $parser = new Parser($options);
        $ast = $parser->parse($tokens);

        return $ast->toPhp();
    }

    /**
     * Encode data to TOON format and write to a file.
     *
     * @throws CircularReferenceException
     * @throws UnencodableException
     * @throws \RuntimeException if file cannot be written
     */
    public static function encodeToFile(mixed $data, string $filePath, ?EncodeOptions $options = null): void
    {
        $toon = self::encode($data, $options);

        $result = file_put_contents($filePath, $toon);
        if ($result === false) {
            throw new \RuntimeException("Failed to write to file: {$filePath}");
        }
    }

    /**
     * Read TOON content from a file and decode it.
     *
     * @return mixed
     * @throws \RuntimeException if file cannot be read
     */
    public static function decodeFromFile(string $filePath, ?DecodeOptions $options = null): mixed
    {
        if (!file_exists($filePath)) {
            throw new \RuntimeException("File not found: {$filePath}");
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new \RuntimeException("Failed to read file: {$filePath}");
        }

        return self::decode($content, $options);
    }

    /**
     * Validate TOON content without fully parsing it.
     * Returns a ValidationResult indicating whether the TOON is valid.
     */
    public static function validate(string $toon, ?DecodeOptions $options = null): ValidationResult
    {
        try {
            self::decode($toon, $options);
            return ValidationResult::success();
        } catch (\Throwable $e) {
            return ValidationResult::failure($e);
        }
    }
}
