<?php

// ABOUTME: Main facade for encoding and decoding TOON format.
// ABOUTME: Provides simple static API for all TOON operations.

declare(strict_types=1);

namespace Toon;

use Toon\Decoder\Tokenizer;
use Toon\Exception\UnencodableException;

final readonly class Toon
{
    public static function encode(mixed $data, ?EncodeOptions $options = null): string
    {
        throw new UnencodableException('Encoder not yet implemented');
    }

    /**
     * @return mixed
     */
    public static function decode(string $toon, ?DecodeOptions $options = null): mixed
    {
        // Minimal implementation for testing - will be replaced with full parser
        $tokenizer = new Tokenizer();
        $tokens = $tokenizer->tokenize($toon);

        // Very basic object construction for testing
        $result = [];
        foreach ($tokens as $token) {
            if ($token->type === \Toon\Decoder\TokenType::ObjectKey) {
                $parts = explode(':', $token->value, 2);
                if (count($parts) === 2) {
                    $result[trim($parts[0])] = trim($parts[1]);
                }
            }
        }

        return $result;
    }
}
