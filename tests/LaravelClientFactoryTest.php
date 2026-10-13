<?php

declare(strict_types=1);

namespace SpacedTracker\Laravel\Tests;

use SpacedTracker\Laravel\LaravelClientFactory;
use SpacedTracker\Log\RedactingLogger;
use SpacedTracker\SpaceTrackClient;
use SpacedTracker\Testing\ArrayLogger;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use ReflectionProperty;
use RuntimeException;

final class LaravelClientFactoryTest extends TestCase
{
    public function testMakeReturnsAClientWithoutPerformingHttpAndRedactsThePassword(): void
    {
        $http = new class implements ClientInterface {
            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                throw new RuntimeException('HTTP must not run during client construction.');
            }
        };
        $logger = new ArrayLogger();

        $client = LaravelClientFactory::make([
            'identity' => 'ada@example.com',
            'password' => 'correct-horse-battery',
            'rate_per_minute' => 5,
            'rate_per_hour' => 20,
        ], $logger, $http);

        self::assertInstanceOf(SpaceTrackClient::class, $client);
        self::assertSame($http, (new ReflectionProperty(SpaceTrackClient::class, 'http'))->getValue($client));
        self::assertFalse($logger->contains('correct-horse-battery'));

        $outer = (new ReflectionProperty(SpaceTrackClient::class, 'logger'))->getValue($client);
        self::assertInstanceOf(RedactingLogger::class, $outer);
        $inner = (new ReflectionProperty(RedactingLogger::class, 'inner'))->getValue($outer);
        self::assertInstanceOf(RedactingLogger::class, $inner);
        self::assertSame($logger, (new ReflectionProperty(RedactingLogger::class, 'inner'))->getValue($inner));
    }
}
