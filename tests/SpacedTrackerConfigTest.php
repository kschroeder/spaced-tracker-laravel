<?php

declare(strict_types=1);

namespace SpacedTracker\Laravel\Tests;

use PHPUnit\Framework\TestCase;

final class SpacedTrackerConfigTest extends TestCase
{
    public function testConfigFileExposesTwelveTypedKeys(): void
    {
        $config = require dirname(__DIR__) . '/config/spaced-tracker.php';

        self::assertIsArray($config);
        self::assertSame([
            'identity',
            'password',
            'base_url',
            'timeout',
            'verify_tls',
            'rate_per_minute',
            'rate_per_hour',
            'log_predicate_values',
            'session_ttl',
            'user_agent',
            'enforce_advisory_limits',
            'concurrency',
        ], array_keys($config));
        self::assertIsString($config['identity']);
        self::assertIsString($config['password']);
        self::assertIsString($config['base_url']);
        self::assertIsFloat($config['timeout']);
        self::assertIsBool($config['verify_tls']);
        self::assertIsInt($config['rate_per_minute']);
        self::assertIsInt($config['rate_per_hour']);
        self::assertIsBool($config['log_predicate_values']);
        self::assertIsInt($config['session_ttl']);
        self::assertIsString($config['user_agent']);
        self::assertIsBool($config['enforce_advisory_limits']);
        self::assertIsString($config['concurrency']);
    }

    public function testFallbackEnvCastsBooleanAndIntegerStrings(): void
    {
        $script = <<<'PHP'
<?php
putenv('SPACETRACK_VERIFY_TLS=false');
putenv('SPACETRACK_LOG_PREDICATE_VALUES=true');
putenv('SPACETRACK_ENFORCE_ADVISORY=0');
putenv('SPACETRACK_RATE_PER_MINUTE=15');
putenv('SPACETRACK_RATE_PER_HOUR=100');
putenv('SPACETRACK_SESSION_TTL=90');
putenv('SPACETRACK_TIMEOUT=12.5');
putenv('SPACETRACK_IDENTITY=ada@example.com');
putenv('SPACETRACK_CONCURRENCY=sequential');
$config = require $argv[1];
fwrite(STDOUT, json_encode([
    'keys' => array_keys($config),
    'verify_tls' => $config['verify_tls'],
    'log_predicate_values' => $config['log_predicate_values'],
    'enforce_advisory_limits' => $config['enforce_advisory_limits'],
    'rate_per_minute' => $config['rate_per_minute'],
    'rate_per_hour' => $config['rate_per_hour'],
    'session_ttl' => $config['session_ttl'],
    'timeout' => $config['timeout'],
    'identity' => $config['identity'],
    'concurrency' => $config['concurrency'],
    'verify_type' => gettype($config['verify_tls']),
    'rate_type' => gettype($config['rate_per_minute']),
    'timeout_type' => gettype($config['timeout']),
    'enforce_type' => gettype($config['enforce_advisory_limits']),
    'password_type' => gettype($config['password']),
], JSON_THROW_ON_ERROR));
PHP;
        $decoded = $this->runConfigScript($script);

        self::assertCount(12, $decoded['keys']);
        self::assertFalse($decoded['verify_tls']);
        self::assertTrue($decoded['log_predicate_values']);
        self::assertFalse($decoded['enforce_advisory_limits']);
        self::assertSame(15, $decoded['rate_per_minute']);
        self::assertSame(100, $decoded['rate_per_hour']);
        self::assertSame(90, $decoded['session_ttl']);
        self::assertSame(12.5, $decoded['timeout']);
        self::assertSame('ada@example.com', $decoded['identity']);
        self::assertSame('sequential', $decoded['concurrency']);
        self::assertSame('boolean', $decoded['verify_type']);
        self::assertSame('integer', $decoded['rate_type']);
        self::assertSame('double', $decoded['timeout_type']);
        self::assertSame('boolean', $decoded['enforce_type']);
        self::assertSame('string', $decoded['password_type']);
    }

    /**
     * @return array<string, mixed>
     */
    private function runConfigScript(string $script): array
    {
        $tmp = tempnam(sys_get_temp_dir(), 'st-config-');
        if ($tmp === false) {
            self::fail('Could not create a temporary config runner.');
        }
        file_put_contents($tmp, $script);

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $process = proc_open(
            [PHP_BINARY, $tmp, dirname(__DIR__) . '/config/spaced-tracker.php'],
            $descriptors,
            $pipes,
        );
        self::assertIsResource($process);
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);
        unlink($tmp);

        self::assertSame(0, $exitCode, (string) $stderr);
        $decoded = json_decode((string) $stdout, true);
        self::assertIsArray($decoded);

        return $decoded;
    }
}
