<?php

declare(strict_types=1);

namespace Ninja\Cartoon\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Ninja\Cartoon\Toon;

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

    public function test_encode_simple_object(): void
    {
        $data = ['name' => 'Alice', 'age' => 30];
        $result = Toon::encode($data);

        $expected = <<<TOON
name: Alice
age: 30
TOON;

        $this->assertSame($expected, $result);
    }

    public function test_round_trip_object(): void
    {
        $original = [
            'name' => 'Charlie',
            'age' => 35,
            'active' => true,
        ];

        $encoded = Toon::encode($original);
        $decoded = Toon::decode($encoded);

        $this->assertSame($original, $decoded);
    }

    public function test_round_trip_nested(): void
    {
        $original = [
            'user' => [
                'name' => 'Diana',
                'age' => 28,
            ],
            'active' => true,
        ];

        $encoded = Toon::encode($original);
        $decoded = Toon::decode($encoded);

        $this->assertSame($original, $decoded);
    }
}
