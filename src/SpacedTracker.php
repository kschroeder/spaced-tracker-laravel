<?php

declare(strict_types=1);

namespace SpacedTracker\Laravel;

use Illuminate\Support\Facades\Facade;
use SpacedTracker\SpaceTrackClientInterface;

/**
 * @mixin \SpacedTracker\SpaceTrackClientInterface
 */
final class SpacedTracker extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SpaceTrackClientInterface::class;
    }
}
