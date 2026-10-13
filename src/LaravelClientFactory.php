<?php

declare(strict_types=1);

namespace SpacedTracker\Laravel;

use SpacedTracker\ClientBuilder;
use SpacedTracker\Log\RedactingLogger;
use SpacedTracker\SpaceTrackClient;
use Psr\Http\Client\ClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Builds a Space-Track client for the Laravel provider.
 *
 * Any logger is wrapped with RedactingLogger here. ClientBuilder wraps it again.
 */
final class LaravelClientFactory
{
    /**
     * @param array<string, mixed> $config
     */
    public static function make(
        array $config,
        ?LoggerInterface $logger = null,
        ?ClientInterface $http = null,
    ): SpaceTrackClient {
        $builder = ClientBuilder::configure($config);
        if ($logger !== null) {
            $builder = $builder->withLogger(new RedactingLogger($logger));
        }
        if ($http !== null) {
            $builder = $builder->withHttpClient($http);
        }

        return $builder->build();
    }
}
