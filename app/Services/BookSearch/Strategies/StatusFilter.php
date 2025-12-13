<?php

namespace App\Services\BookSearch\Strategies;

use App\Services\BookSearch\Contracts\BookFilterStrategy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class StatusFilter implements BookFilterStrategy
{
    private array $allowed = ['Available', 'Borrowed', 'Lost', 'Damaged'];

    public function apply(Builder $query, Request $request): Builder
    {
        $status = $request->input('status');

        if (!$status || !in_array($status, $this->allowed, true)) {
            return $query;
        }

        return $query->where('status', $status);
    }
}
