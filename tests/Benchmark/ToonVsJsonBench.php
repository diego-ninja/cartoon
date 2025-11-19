<?php

// ABOUTME: Compares TOON performance against JSON for various data scenarios.
// ABOUTME: Measures encoding/decoding speed and output size.

declare(strict_types=1);

namespace Toon\Tests\Benchmark;

use PHPUnit\Framework\TestCase;
use Toon\Exception\CircularReferenceException;
use Toon\Exception\UnencodableException;
use Toon\Toon;

final class ToonVsJsonBench extends TestCase
{
    private const int ITERATIONS = 1000;

    /**
     * @var array<string, array<string, mixed>>
     */
    private array $scenarios;

    protected function setUp(): void
    {
        parent::setUp();

        $this->scenarios = [
            'Small Object' => [
                'id' => 1,
                'name' => 'Alice',
                'active' => true,
            ],

            'Medium Nested' => [
                'user' => [
                    'id' => 123,
                    'name' => 'Bob',
                    'email' => 'bob@example.com',
                    'roles' => ['admin', 'editor', 'viewer'],
                    'active' => true,
                ],
                'settings' => [
                    'theme' => 'dark',
                    'language' => 'en',
                    'notifications' => [
                        'email' => true,
                        'sms' => false,
                        'push' => true,
                    ],
                ],
            ],

            'Array of Objects' => [
                'users' => $this->generateUsers(50),
            ],

            'Large Dataset' => [
                'metadata' => [
                    'version' => '1.0',
                    'generated' => '2025-11-18T10:00:00Z',
                    'count' => 100,
                ],
                'users' => $this->generateUsers(100),
                'settings' => [
                    'maxRetries' => 3,
                    'timeout' => 30,
                    'features' => ['auth', 'logging', 'metrics', 'caching'],
                ],
            ],

            'Deep Nesting' => [
                'level1' => [
                    'level2' => [
                        'level3' => [
                            'level4' => [
                                'level5' => [
                                    'data' => 'deep value',
                                    'count' => 42,
                                ],
                            ],
                        ],
                    ],
                ],
            ],

            'Flat Array' => [
                'numbers' => range(1, 100),
                'strings' => array_map(fn($i) => "item-$i", range(1, 50)),
                'mixed' => [1, 'two', 3.14, true, null, false],
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function generateUsers(int $count): array
    {
        $users = [];
        for ($i = 0; $i < $count; $i++) {
            // Use heterogeneous data to avoid tabular format
            $user = [
                'id' => $i + 1,
                'name' => 'User ' . ($i + 1),
                'email' => 'user' . ($i + 1) . '@example.com',
            ];

            // Add varying fields to prevent tabular encoding
            if ($i % 2 === 0) {
                $user['status'] = 'active';
                $user['metadata'] = ['verified' => true];
            } else {
                $user['score'] = ($i * 10) % 100;
            }

            $users[] = $user;
        }
        return $users;
    }

    /**
     * @throws CircularReferenceException
     * @throws UnencodableException
     */
    public function test_toon_vs_json_benchmark(): void
    {
        echo "\n" . str_repeat('=', 100) . "\n";
        echo "TOON vs JSON Performance Comparison\n";
        echo str_repeat('=', 100) . "\n\n";

        $results = [];

        foreach ($this->scenarios as $name => $data) {
            $results[$name] = $this->benchmarkScenario($name, $data);
        }

        $this->printSummaryTable($results);
        $this->addToAssertionCount(1); // Benchmark always passes
    }

    /**
     * @param string $name
     * @param array<string, mixed> $data
     * @return array{toon_encode: float, json_encode: float, toon_decode: float, json_decode: float, toon_size: int, json_size: int}
     * @throws CircularReferenceException
     * @throws UnencodableException
     */
    private function benchmarkScenario(string $name, array $data): array
    {
        $decodeOptions = new \Toon\DecodeOptions(strict: false);

        // Warm up
        Toon::encode($data);
        json_encode($data);

        // Measure TOON encoding
        $toonEncodeStart = microtime(true);
        $toonEncoded = '';
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $toonEncoded = Toon::encode($data);
        }
        $toonEncodeTime = (microtime(true) - $toonEncodeStart) / self::ITERATIONS;
        $toonSize = strlen($toonEncoded);

        // Measure JSON encoding
        $jsonEncodeStart = microtime(true);
        $jsonEncoded = '';
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $jsonEncoded = json_encode($data);
            if ($jsonEncoded === false) {
                throw new \RuntimeException('json_encode failed');
            }
        }
        $jsonEncodeTime = (microtime(true) - $jsonEncodeStart) / self::ITERATIONS;
        $jsonSize = strlen($jsonEncoded);

        // Measure TOON decoding
        $toonDecodeStart = microtime(true);
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            Toon::decode($toonEncoded, $decodeOptions);
        }
        $toonDecodeTime = (microtime(true) - $toonDecodeStart) / self::ITERATIONS;

        // Measure JSON decoding
        $jsonDecodeStart = microtime(true);
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $decoded = json_decode($jsonEncoded, true);
            if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
                throw new \RuntimeException('json_decode failed');
            }
        }
        $jsonDecodeTime = (microtime(true) - $jsonDecodeStart) / self::ITERATIONS;

        return [
            'toon_encode' => $toonEncodeTime,
            'json_encode' => $jsonEncodeTime,
            'toon_decode' => $toonDecodeTime,
            'json_decode' => $jsonDecodeTime,
            'toon_size' => $toonSize,
            'json_size' => $jsonSize,
        ];
    }

    /**
     * @param array<string, array{toon_encode: float, json_encode: float, toon_decode: float, json_decode: float, toon_size: int, json_size: int}> $results
     */
    private function printSummaryTable(array $results): void
    {
        echo sprintf("%-20s | %-15s | %-15s | %-10s\n", "Scenario", "Encode (μs)", "Decode (μs)", "Size (bytes)");
        echo str_repeat('-', 100) . "\n";

        foreach ($results as $name => $result) {
            $toonEncodeUs = $result['toon_encode'] * 1_000_000;
            $toonDecodeUs = $result['toon_decode'] * 1_000_000;
            $jsonEncodeUs = $result['json_encode'] * 1_000_000;
            $jsonDecodeUs = $result['json_decode'] * 1_000_000;

            echo sprintf(
                "%-20s | TOON: %8.2f | TOON: %8.2f | TOON: %6d\n",
                $name,
                $toonEncodeUs,
                $toonDecodeUs,
                $result['toon_size'],
            );

            $encodeRatio = $result['toon_encode'] / $result['json_encode'];
            $decodeRatio = $result['toon_decode'] / $result['json_decode'];
            $sizeRatio = $result['toon_size'] / $result['json_size'];

            echo sprintf(
                "%-20s | JSON: %8.2f | JSON: %8.2f | JSON: %6d\n",
                "",
                $jsonEncodeUs,
                $jsonDecodeUs,
                $result['json_size'],
            );

            echo sprintf(
                "%-20s | Ratio: %7.2fx | Ratio: %7.2fx | Ratio: %5.2fx\n",
                "",
                $encodeRatio,
                $decodeRatio,
                $sizeRatio,
            );

            echo str_repeat('-', 100) . "\n";
        }

        echo "\n";
        echo "Notes:\n";
        echo "  - Ratio > 1.0 means TOON is slower/larger than JSON\n";
        echo "  - Ratio < 1.0 means TOON is faster/smaller than JSON\n";
        echo "  - All measurements averaged over " . number_format(self::ITERATIONS) . " iterations\n";
        echo "\n";
    }
}
