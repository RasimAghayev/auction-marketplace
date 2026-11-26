<?php

namespace App\Support\Concerns;

trait ResolvesDatabaseConnection
{
    protected function resolveConnection(): string
    {
        $connection = config('database.default');

        if (app()->environment('testing') || ($_ENV['APP_ENV'] ?? '') === 'testing' || (getenv('APP_ENV') ?? '') === 'testing') {
            $connection = 'mongodb_testing';
        }

        return $connection;
    }
}
