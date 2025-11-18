<?php

declare(strict_types=1);

namespace Toon\Tests\Benchmark;

use PHPUnit\Framework\TestCase;
use Toon\Toon;

final class EncodeDecodeBench extends TestCase
{
    private const int ITERATIONS = 1000; // Number of times to repeat encode/decode operations for measurement

    /**
     * @var array<string, mixed>
     */
    private array $smallData;

    /**
     * @var array<string, mixed>
     */
    private array $mediumData;

    /**
     * @var array<string, mixed>
     */
    private array $largeData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->smallData = [
            'id' => 1,
            'name' => 'Alice',
        ];

        $this->mediumData = [
            'user' => [
                'id' => 123,
                'name' => 'Bob',
                'email' => 'bob@example.com',
                'roles' => ['admin', 'editor'],
                'isActive' => true,
            ],
            'settings' => [
                'theme' => 'dark',
                'notifications' => [
                    'email' => true,
                    'sms' => false,
                ],
            ],
            'lastLogin' => '2025-11-18T10:00:00Z',
        ];

        $this->largeData = $this->generateLargeData(100); // 100 users
    }

    /**
     * Generates an array of user objects for large data benchmarks.
     *
     * @return array<string, mixed>
     */
    private function generateLargeData(int $count): array
    {
        $data = [];
        for ($i = 0; $i < $count; $i++) {
            $data[] = [
                'id' => $i + 1,
                'name' => 'User ' . ($i + 1),
                'email' => 'user' . ($i + 1) . '@example.com',
                'status' => $i % 2 === 0 ? 'active' : 'inactive',
                'createdAt' => '2025-01-01T' . str_pad((string)($i % 24), 2, '0', STR_PAD_LEFT) . ':00:00Z',
            ];
        }
        return ['users' => $data];
    }

    // -------------------------------------------------------------------------
    // ENCODE BENCHMARKS
    // -------------------------------------------------------------------------

    public function test_encode_small_data_performance(): void
    {
        $this->runBenchmark(
            'Encode Small Data',
            fn() => Toon::encode($this->smallData),
            fn($result) => $this->assertIsString($result),
        );
    }

    public function test_encode_medium_data_performance(): void
    {
        $this->runBenchmark(
            'Encode Medium Data',
            fn() => Toon::encode($this->mediumData),
            fn($result) => $this->assertIsString($result),
        );
    }

    public function test_encode_large_data_performance(): void
    {
        $this->runBenchmark(
            'Encode Large Data (100 users)',
            fn() => Toon::encode($this->largeData),
            fn($result) => $this->assertIsString($result),
        );
    }

    // -------------------------------------------------------------------------
    // DECODE BENCHMARKS
    // -------------------------------------------------------------------------

    public function test_decode_small_data_performance(): void
    {
        $encoded = Toon::encode($this->smallData);
        $this->runBenchmark(
            'Decode Small Data',
            fn() => Toon::decode($encoded),
            fn($result) => $this->assertIsArray($result),
        );
    }

    public function test_decode_medium_data_performance(): void
    {
        $encoded = Toon::encode($this->mediumData);
        $this->runBenchmark(
            'Decode Medium Data',
            fn() => Toon::decode($encoded),
            fn($result) => $this->assertIsArray($result),
        );
    }

    public function test_decode_large_data_performance(): void
    {
        $encoded = Toon::encode($this->largeData);
        $this->runBenchmark(
            'Decode Large Data (100 users)',
            fn() => Toon::decode($encoded),
            fn($result) => $this->assertIsArray($result),
        );
    }

    // -------------------------------------------------------------------------
    // HELPER
    // -------------------------------------------------------------------------

    /**
     * Runs a benchmark for a given callable and reports performance metrics.
     *
     * @param string $name Name of the benchmark.
     * @param callable(): mixed $operation The operation to benchmark.
     * @param callable(mixed): void $assertion An assertion to run after each operation for sanity check.
     */
    private function runBenchmark(string $name, callable $operation, callable $assertion): void
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $result = $operation();
            $assertion($result);
        }

        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);

        $duration = ($endTime - $startTime) / self::ITERATIONS;
        $memoryDiff = ($endMemory - $startMemory) / self::ITERATIONS; // Average memory increase per iteration

        echo "\nBenchmark: {$name}\n";
        echo sprintf("  Avg Time: %.6f ms\n", $duration * 1000);
        echo sprintf("  Avg Memory: %s KB\n", number_format($memoryDiff / 1024, 2));
    }
}
