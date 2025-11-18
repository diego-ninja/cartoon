<?php

declare(strict_types=1);

namespace Toon\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Toon\Toon;

final class BasicRoundTripTest extends TestCase
{
    public function test_decode_simple_object(): void
    {
        $toon = <<<TOON
name: Alice
age: 30
TOON;

        $result = Toon::decode($toon);

        $this->assertSame(['name' => 'Alice', 'age' => 30], $result);
    }

    public function test_decode_nested_object(): void
    {
        $toon = <<<TOON
user:
  name: Bob
  age: 25
TOON;

        $result = Toon::decode($toon);

        $expected = [
            'user' => [
                'name' => 'Bob',
                'age' => 25,
            ],
        ];

        $this->assertSame($expected, $result);
    }
}
