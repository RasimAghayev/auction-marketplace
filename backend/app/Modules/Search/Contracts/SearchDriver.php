<?php

namespace App\Modules\Search\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface SearchDriver
{
    public function search(string $query, array $filters = []): LengthAwarePaginator;
}
