<?php

declare(strict_types=1);

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        $value = getenv($key);
        if ($value === false || $value === '') {
            return $default;
        }

        return $value;
    }
}

$asString = static function (mixed $value, string $default): string {
    if (is_string($value)) {
        return $value;
    }
    if (is_int($value) || is_float($value)) {
        return (string) $value;
    }

    return $default;
};

$asBool = static function (mixed $value, bool $default): bool {
    if (is_bool($value)) {
        return $value;
    }
    if (is_int($value)) {
        return $value === 1;
    }
    if (!is_string($value) || $value === '') {
        return $default;
    }

    return in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true);
};

$asInt = static function (mixed $value, int $default): int {
    if (is_int($value)) {
        return $value;
    }
    if (is_float($value) || (is_string($value) && is_numeric($value))) {
        return (int) $value;
    }

    return $default;
};

$asFloat = static function (mixed $value, float $default): float {
    if (is_int($value) || is_float($value)) {
        return (float) $value;
    }
    if (is_string($value) && is_numeric($value)) {
        return (float) $value;
    }

    return $default;
};

return [
    'identity' => $asString(env('SPACETRACK_IDENTITY', ''), ''),
    'password' => $asString(env('SPACETRACK_PASSWORD', ''), ''),
    'base_url' => $asString(env('SPACETRACK_BASE_URL', 'https://www.space-track.org'), 'https://www.space-track.org'),
    'timeout' => $asFloat(env('SPACETRACK_TIMEOUT', 60), 60.0),
    'verify_tls' => $asBool(env('SPACETRACK_VERIFY_TLS', true), true),
    'rate_per_minute' => $asInt(env('SPACETRACK_RATE_PER_MINUTE', 29), 29),
    'rate_per_hour' => $asInt(env('SPACETRACK_RATE_PER_HOUR', 299), 299),
    'log_predicate_values' => $asBool(env('SPACETRACK_LOG_PREDICATE_VALUES', false), false),
    'session_ttl' => $asInt(env('SPACETRACK_SESSION_TTL', 7200), 7200),
    'user_agent' => $asString(
        env('SPACETRACK_USER_AGENT', 'kschroeder-spaced-tracker/1.0'),
        'kschroeder-spaced-tracker/1.0',
    ),
    'enforce_advisory_limits' => $asBool(env('SPACETRACK_ENFORCE_ADVISORY', false), false),
    'concurrency' => $asString(env('SPACETRACK_CONCURRENCY', 'auto'), 'auto'),
];
