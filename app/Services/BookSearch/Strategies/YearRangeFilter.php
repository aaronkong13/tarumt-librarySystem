<?php

namespace App\Services\BookSearch\Strategies;

use App\Services\BookSearch\Contracts\BookFilterStrategy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class YearRangeFilter implements BookFilterStrategy
{
    public function apply(Builder $query, Request $request): Builder
    {
        $from = $request->input('year_from');
        $to = $request->input('year_to');

        if ($from !== null && is_numeric($from)) {
            $query->where('year', '>=', (int) $from);
        }

        if ($to !== null && is_numeric($to)) {
            $query->where('year', '<=', (int) $to);
        }

        return $query;
    }
}
