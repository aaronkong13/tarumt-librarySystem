<?php

namespace App\Services\BookSearch\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

interface BookFilterStrategy
{
    /**
     * Apply a filter to the incoming book query.
     */
    public function apply(Builder $query, Request $request): Builder;
}
