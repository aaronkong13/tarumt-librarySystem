<?php

namespace App\Services\BookSearch\Strategies;

use App\Services\BookSearch\Contracts\BookFilterStrategy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class CategoryFilter implements BookFilterStrategy
{
    public function apply(Builder $query, Request $request): Builder
    {
        $category = trim((string) $request->input('category', ''));

        if ($category === '') {
            return $query;
        }

        return $query->where('category', 'like', "%{$category}%");
    }
}
