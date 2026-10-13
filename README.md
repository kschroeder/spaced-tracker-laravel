# kschroeder/spaced-tracker-laravel

Laravel integration for [kschroeder/spaced-tracker](https://github.com/kschroeder/spaced-tracker), version 1.0.0. MIT license.

This package exists because a service provider and a facade must extend Laravel classes. The Space-Track client itself is framework-agnostic. Mezzio, Slim, and any other PSR-11 container should use `SpacedTracker\Integration\Psr11ClientFactory` from the core package instead of this one. Mezzio can register `SpacedTracker\Integration\Mezzio\ConfigProvider`.

Illuminate components are constrained to `illuminate/support`, `illuminate/contracts`, `illuminate/config`, and `illuminate/container` at `^11.0 || ^12.0 || ^13.0`. PHP is `^8.2`. `vlucas/phpdotenv` `^5.6.1` is required as well: `illuminate/support` only suggests it, but the published config calls Laravel's `env()` helper, and that helper needs phpdotenv. A normal Laravel application already installs both the container and phpdotenv.

## Install

Install [kschroeder/spaced-tracker-laravel](https://packagist.org/packages/kschroeder/spaced-tracker-laravel) from Packagist. Composer installs `kschroeder/spaced-tracker` from Packagist as well:

```bash
composer require kschroeder/spaced-tracker-laravel:^1.0
```

To work on this repository, clone it and run `composer install`. That clones `kschroeder/spaced-tracker` from GitHub, because this checkout's `composer.json` lists that repository. Replace that entry with a path repository of `../spaced-tracker` when Composer should symlink a neighboring checkout instead.

Laravel package discovery registers `SpacedTracker\Laravel\SpacedTrackerServiceProvider` and the `SpacedTracker` facade alias. Register the provider yourself only if discovery is disabled.

The service provider builds the client with `SpacedTracker\Laravel\LaravelClientFactory`. PSR-17 factories come from `nyholm/psr7`, which this package requires. It does **not** require an HTTP client. Bind `Psr\Http\Client\ClientInterface` in the container, or install `symfony/http-client` or `guzzlehttp/guzzle`, so `ClientBuilder` can open connections. An existing `SpacedTracker\SpaceTrackClientInterface` binding is left in place.

## Configure

Publish the config file:

```bash
php artisan vendor:publish --tag=spaced-tracker-config
```

That copies `config/spaced-tracker.php`. Laravel loads it as the `spaced-tracker` key. Values already in that key override the package defaults. Set credentials in the environment, not in the committed file:

```dotenv
SPACETRACK_IDENTITY=ada@example.com
SPACETRACK_PASSWORD=your-space-track-password
```

Laravel's `env()` helper treats the words `true`, `false`, `null`, and `empty` as booleans and null. Quote a password that is one of those words. This package still casts booleans and integers itself, so `"false"` is boolean `false` and rate limits are integers. The string `"false"` is not true.

| Environment variable | Config key | Default |
| --- | --- | --- |
| `SPACETRACK_IDENTITY` | `identity` | empty |
| `SPACETRACK_PASSWORD` | `password` | empty |
| `SPACETRACK_BASE_URL` | `base_url` | `https://www.space-track.org` |
| `SPACETRACK_TIMEOUT` | `timeout` | `60` |
| `SPACETRACK_VERIFY_TLS` | `verify_tls` | `true` |
| `SPACETRACK_RATE_PER_MINUTE` | `rate_per_minute` | `29` |
| `SPACETRACK_RATE_PER_HOUR` | `rate_per_hour` | `299` |
| `SPACETRACK_LOG_PREDICATE_VALUES` | `log_predicate_values` | `false` |
| `SPACETRACK_SESSION_TTL` | `session_ttl` | `7200` |
| `SPACETRACK_USER_AGENT` | `user_agent` | `kschroeder-spaced-tracker/1.0` |
| `SPACETRACK_ENFORCE_ADVISORY` | `enforce_advisory_limits` | `false` |
| `SPACETRACK_CONCURRENCY` | `concurrency` | `auto` |

`concurrency` is `auto`, `sequential`, `curl`, `symfony`, `guzzle`, or `amp`. Boolean values accept `1`, `true`, `yes`, and `on`. Anything else, including `false` and `0`, is false when the variable is set.

Space-Track asks callers to stay under 30 requests per minute and 300 per hour. The defaults are one below those advisory numbers. Setting `SPACETRACK_ENFORCE_ADVISORY=true` makes the client refuse queries that would cross the published advisory caps.

## Inject the client

```php
use SpacedTracker\Query\Query;
use SpacedTracker\SpaceTrackClientInterface;

final class SatelliteLookup
{
    public function __construct(private readonly SpaceTrackClientInterface $spaceTrack)
    {
    }

    public function iss(): mixed
    {
        return $this->spaceTrack->execute(
            Query::create('gp')->where('NORAD_CAT_ID', 25544)->limit(1),
        );
    }
}
```

The container also aliases `SpacedTracker\SpaceTrackClient` to that interface. Prefer the interface.

The facade accessor is the same interface:

```php
use SpacedTracker\Laravel\SpacedTracker;

SpacedTracker::hasSession();
```

A `Psr\Log\LoggerInterface` binding is passed into the factory. A `Psr\Http\Client\ClientInterface` binding is used as the HTTP client.

## Security

Do not commit `SPACETRACK_PASSWORD` or write it into `config/spaced-tracker.php`. Do not log it.

`LaravelClientFactory` wraps any logger in `SpacedTracker\Log\RedactingLogger` before `ClientBuilder` sees it. `ClientBuilder` wraps the logger again. The second wrap is harmless. The redactor strips passwords, identities, cookies, and bearer tokens from messages and context. The core client also redacts request paths when predicate logging is enabled.

Keep `SPACETRACK_LOG_PREDICATE_VALUES` false unless you need predicate text in logs. Paths can still contain identifiers.

Copyright 2026 Kevin Schroeder.
