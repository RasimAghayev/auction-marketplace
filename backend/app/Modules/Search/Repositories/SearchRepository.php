<?php

namespace App\Modules\Search\Repositories;

use App\Modules\Search\Contracts\SearchDriver;
use App\Modules\Search\Drivers\SqlSearchDriver;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\App;

class SearchRepository
{
    public function __construct(
        protected SearchDriver $driver
    ) {}

    public function products(string $query, array $filters = []): LengthAwarePaginator
    {
        return $this->driver->search($query, $filters);
    }
}
