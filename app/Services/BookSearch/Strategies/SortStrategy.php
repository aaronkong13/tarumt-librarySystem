<?php

namespace App\Services\BookSearch\Strategies;

use App\Services\BookSearch\Contracts\BookFilterStrategy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class SortStrategy implements BookFilterStrategy
{
    public function apply(Builder $query, Request $request): Builder
    {
        $sort = $request->input('sort');

        return match ($sort) {
            'title' => $query->orderBy('title'),
            'year' => $query->orderBy('year', 'desc'),
            default => $query->orderByDesc('created_at'),
        };
    }
}
