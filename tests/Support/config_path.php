<?php

declare(strict_types=1);

function config_path(string $path = ''): string
{
    $base = '/var/laravel/config';
    if ($path === '') {
        return $base;
    }

    return $base . '/' . $path;
}
