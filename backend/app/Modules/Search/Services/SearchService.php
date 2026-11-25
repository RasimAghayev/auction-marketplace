<?php

namespace App\Modules\Search\Services;

use App\Modules\Search\Repositories\SearchRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SearchService
{
    public function __construct(
        protected SearchRepository $repository
    ) {}

    public function products(string $query, array $filters = []): LengthAwarePaginator
    {
        return $this->repository->products($query, $filters);
    }
}
