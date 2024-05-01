<?php

namespace App\Http\Filters\Api\V1;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class OrderBy
{
    public function __construct(protected Request $request)
    {
        //
    }

    public function handle(Builder $builder, \Closure $next)
    {
        return $next($builder)
            ->when(isset($this->request->sort_by) && isset($this->request->sort), function ($query) {
                $query->orderBy($this->request->sort_by, ($this->request->sort == -1 ? 'DESC' : 'ASC'));
            });
    }
}
