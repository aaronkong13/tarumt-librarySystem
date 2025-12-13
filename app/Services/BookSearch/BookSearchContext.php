<?php

namespace App\Services\BookSearch;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use App\Services\BookSearch\Contracts\BookFilterStrategy;

class BookSearchContext
{
    /** @var BookFilterStrategy[] */
    private array $strategies;

    public function __construct(array $strategies = [])
    {
        $this->strategies = $strategies;
    }

    public function apply(Builder $query, Request $request): Builder
    {
        foreach ($this->strategies as $strategy) {
            $query = $strategy->apply($query, $request);
        }

        return $query;
    }
}
