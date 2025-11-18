<?php

// ABOUTME: Main facade for encoding and decoding TOON format.
// ABOUTME: Provides simple static API for all TOON operations.

declare(strict_types=1);

namespace Toon;

use Toon\Decoder\Parser;
use Toon\Decoder\Tokenizer;
use Toon\Encoder\Writer;
use Toon\Exception\CircularReferenceException;
use Toon\Exception\UnencodableException;

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
}
