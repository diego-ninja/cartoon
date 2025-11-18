<?php

use Toon\DecodeOptions;
use Toon\EncodeOptions;
use Toon\Exception\CircularReferenceException;
use Toon\Exception\UnencodableException;
use Toon\Toon;

if (! function_exists('toon_encode')) {
    /**
     * Encode a value to TOON format.
     *
     * @param mixed $value The value to encode
     * @param EncodeOptions|null $options Optional encoding options
     * @return string The TOON-encoded string
     * @throws CircularReferenceException
     * @throws UnencodableException
     */
    function toon_encode(mixed $value, ?EncodeOptions $options = null): string
    {
        return Toon::encode($value, $options ?? EncodeOptions::default());
    }
}

if (! function_exists('toon_decode')) {
    /**
     * Decode a TOON string to a PHP value.
     *
     * Parses TOON format back into PHP data structures (arrays, primitives).
     * Objects are decoded as associative arrays.
     *
     * @param  string  $toon  The TOON-formatted string to decode
     * @param  DecodeOptions|null  $options  Optional decoding options
     * @return mixed The decoded PHP value
     */
    function toon_decode(string $toon, ?DecodeOptions $options = null): mixed
    {
        return Toon::decode($toon, $options ?? DecodeOptions::default());
    }
}
