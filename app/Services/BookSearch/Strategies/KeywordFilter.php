<?php

namespace App\Services\BookSearch\Strategies;

use App\Services\BookSearch\Contracts\BookFilterStrategy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class KeywordFilter implements BookFilterStrategy
{
    public function apply(Builder $query, Request $request): Builder
    {
        $term = trim((string) $request->input('q', ''));

        if ($term === '') {
            return $query;
        }

        // Eloquent bindings handle escaping (OWASP [21]).
        return $query->where(function (Builder $inner) use ($term) {
            $inner->where('title', 'like', "%{$term}%")
                  ->orWhere('author', 'like', "%{$term}%")
                  ->orWhere('isbn', 'like', "%{$term}%")
                  ->orWhere('category', 'like', "%{$term}%");
        });
    }
}
