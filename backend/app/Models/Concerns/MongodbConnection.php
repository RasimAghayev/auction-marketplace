<?php

namespace App\Models\Concerns;

trait MongodbConnection
{
    public function getConnectionName(): string
    {
        if (app()->environment('testing') || ($_ENV['APP_ENV'] ?? '') === 'testing' || (getenv('APP_ENV') ?? '') === 'testing') {
            return 'mongodb_testing';
        }

        return 'mongodb';
    }
}
