<?php

namespace App\Http\Filters\Api\V1;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ByRangeDate
{
    public function __construct(protected Request $request)
    {
        //
    }

    public function handle(Builder $builder, \Closure $next)
    {
        return $next($builder)
            ->when($this->request->has('start_date') && $this->request->has('end_date'), function ($query) {
                $query->where('date', '>=', $this->request->start_date.' 00:00:00')
                    ->where('date', '<=', $this->request->end_date.' 23:59:59');
            });
    }
}
