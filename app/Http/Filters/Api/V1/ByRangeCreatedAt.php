<?php

namespace App\Http\Filters\Api\V1;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ByRangeCreatedAt
{
    public function __construct(protected Request $request)
    {
        //
    }

    public function handle(Builder $builder, \Closure $next)
    {
        return $next($builder)
            ->when($this->request->has('start_date') && $this->request->has('end_date'), function ($query) {
                $query->where('created_at', '>=', $this->request->start_date.' 00:00:00')
                    ->where('created_at', '<=', $this->request->end_date.' 23:59:59');
            });
    }
}
