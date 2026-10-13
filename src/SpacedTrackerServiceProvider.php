<?php

declare(strict_types=1);

namespace SpacedTracker\Laravel;

use Illuminate\Container\Container;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\ServiceProvider;
use SpacedTracker\SpaceTrackClient;
use SpacedTracker\SpaceTrackClientInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Log\LoggerInterface;

class SpacedTrackerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom($this->configPath(), 'spaced-tracker');

        if (!$this->app->bound(SpaceTrackClientInterface::class)) {
            $this->app->singleton(SpaceTrackClientInterface::class, static function (Container $app): SpaceTrackClient {
                $repository = $app->make('config');
                $config = [];
                if ($repository instanceof Repository) {
                    $loaded = $repository->get('spaced-tracker');
                    if (is_array($loaded)) {
                        /** @var array<string, mixed> $loaded */
                        $config = $loaded;
                    }
                }

                return LaravelClientFactory::make($config, self::logger($app), self::httpClient($app));
            });
        }

        if (!$this->app->bound(SpaceTrackClient::class)) {
            $this->app->alias(SpaceTrackClientInterface::class, SpaceTrackClient::class);
        }
    }

    public function boot(): void
    {
        if (!function_exists('config_path')) {
            return;
        }

        $this->publishes([
            $this->configPath() => config_path('spaced-tracker.php'),
        ], 'spaced-tracker-config');
    }

    private function configPath(): string
    {
        return dirname(__DIR__) . '/config/spaced-tracker.php';
    }

    private static function logger(Container $app): ?LoggerInterface
    {
        if ($app->bound(LoggerInterface::class) !== true) {
            return null;
        }

        return $app->make(LoggerInterface::class);
    }

    private static function httpClient(Container $app): ?ClientInterface
    {
        if ($app->bound(ClientInterface::class) !== true) {
            return null;
        }

        return $app->make(ClientInterface::class);
    }
}
