<?php

declare(strict_types=1);

namespace Toon\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Toon\Toon;

final class BasicRoundTripTest extends TestCase
{
    public function test_decode_simple_object(): void
    {
        $this->markTestSkipped('Waiting for Parser implementation');

        // @phpstan-ignore-next-line
        $toon = <<<TOON
name: Alice
age: 30
TOON;

        $result = Toon::decode($toon);

        $this->assertSame(['name' => 'Alice', 'age' => '30'], $result);
    }
}
