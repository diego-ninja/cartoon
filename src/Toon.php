<?php

// ABOUTME: Main facade for encoding and decoding TOON format.
// ABOUTME: Provides simple static API for all TOON operations.

declare(strict_types=1);

namespace Toon;

use Toon\Decoder\Parser;
use Toon\Decoder\Tokenizer;
use Toon\Encoder\Writer;

final readonly class Toon
{
    public static function encode(mixed $data, ?EncodeOptions $options = null): string
    {
        $options ??= new EncodeOptions();

        $writer = new Writer($options);
        return $writer->write($data);
    }

    /**
     * @return mixed
     */
    public static function decode(string $toon, ?DecodeOptions $options = null): mixed
    {
        $options ??= new DecodeOptions();

        $tokenizer = new Tokenizer();
        $tokens = $tokenizer->tokenize($toon);

        $parser = new Parser($options);
        $ast = $parser->parse($tokens);

        return $ast->toPhp();
    }
}
