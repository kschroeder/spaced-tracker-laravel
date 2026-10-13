<?php

declare(strict_types=1);

namespace SpacedTracker\Laravel\Tests;

use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use SpacedTracker\Exception\ConfigurationException;
use SpacedTracker\Laravel\SpacedTracker;
use SpacedTracker\Laravel\SpacedTrackerServiceProvider;
use SpacedTracker\SpaceTrackClient;
use SpacedTracker\SpaceTrackClientInterface;
use SpacedTracker\Testing\ArrayLogger;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use ReflectionMethod;
use RuntimeException;

final class SpacedTrackerServiceProviderTest extends TestCase
{
    public function testRegisterResolvesASpaceTrackClient(): void
    {
        $http = new class implements ClientInterface {
            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                throw new RuntimeException('HTTP must not run during client construction.');
            }
        };
        $logger = new ArrayLogger();
        $container = $this->container([
            'identity' => 'ada@example.com',
            'password' => 'correct-horse-battery',
            'rate_per_minute' => 5,
            'rate_per_hour' => 20,
        ]);
        $container->instance(ClientInterface::class, $http);
        $container->instance(LoggerInterface::class, $logger);

        $provider = new SpacedTrackerServiceProvider($container);
        $provider->register();

        $client = $container->make(SpaceTrackClientInterface::class);
        self::assertInstanceOf(SpaceTrackClient::class, $client);
        self::assertSame($client, $container->make(SpaceTrackClientInterface::class));
        self::assertSame($client, $container->make(SpaceTrackClient::class));
        self::assertFalse($logger->contains('correct-horse-battery'));

        SpacedTracker::setFacadeApplication($container);
        SpacedTracker::clearResolvedInstances();
        try {
            self::assertFalse(SpacedTracker::hasSession());
        } finally {
            SpacedTracker::clearResolvedInstances();
            SpacedTracker::setFacadeApplication(null);
        }
    }

    public function testRegisterRejectsAnEmptyIdentity(): void
    {
        $container = $this->container([
            'identity' => '',
            'password' => 'correct-horse-battery',
        ]);
        $provider = new SpacedTrackerServiceProvider($container);
        $provider->register();

        $this->expectException(ConfigurationException::class);
        $container->make(SpaceTrackClientInterface::class);
    }

    public function testRegisterKeepsAnExistingClientBinding(): void
    {
        $fake = $this->createMock(SpaceTrackClientInterface::class);
        $container = $this->container([
            'identity' => '',
            'password' => '',
        ]);
        $container->instance(SpaceTrackClientInterface::class, $fake);

        $provider = new SpacedTrackerServiceProvider($container);
        $provider->register();

        self::assertSame($fake, $container->make(SpaceTrackClientInterface::class));
    }

    public function testBootDoesNotPublishWithoutConfigPath(): void
    {
        self::assertFalse(function_exists('config_path'));
        $provider = new SpacedTrackerServiceProvider(new Container());
        $provider->boot();

        self::assertArrayNotHasKey('spaced-tracker-config', SpacedTrackerServiceProvider::$publishGroups);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testBootPublishesConfigWhenConfigPathExists(): void
    {
        require __DIR__ . '/Support/config_path.php';
        self::assertTrue(function_exists('config_path'));

        $provider = new SpacedTrackerServiceProvider(new Container());
        $provider->boot();
        $source = dirname(__DIR__) . '/config/spaced-tracker.php';

        self::assertFileExists($source);
        self::assertSame(
            config_path('spaced-tracker.php'),
            SpacedTrackerServiceProvider::$publishGroups['spaced-tracker-config'][$source],
        );
    }

    public function testComposerDeclaresTheProviderAndFacade(): void
    {
        $composer = json_decode(
            (string) file_get_contents(dirname(__DIR__) . '/composer.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        self::assertContains(
            SpacedTrackerServiceProvider::class,
            $composer['extra']['laravel']['providers'],
        );
        self::assertSame(
            SpacedTracker::class,
            $composer['extra']['laravel']['aliases']['SpacedTracker'],
        );
    }

    public function testFacadeAccessorIsTheClientInterface(): void
    {
        $method = new ReflectionMethod(SpacedTracker::class, 'getFacadeAccessor');
        self::assertSame(SpaceTrackClientInterface::class, $method->invoke(null));
    }

    /**
     * @param array<string, mixed> $spacedTracker
     */
    private function container(array $spacedTracker): Container
    {
        $container = new Container();
        $container->instance('config', new Repository([
            'spaced-tracker' => $spacedTracker,
        ]));

        return $container;
    }
}
