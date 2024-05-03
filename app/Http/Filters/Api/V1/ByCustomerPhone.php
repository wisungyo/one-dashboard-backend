<?php

namespace App\Http\Filters\Api\V1;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ByCustomerPhone
{
    public function __construct(protected Request $request)
    {
        //
    }

    public function handle(Builder $builder, \Closure $next)
    {
        return $next($builder)
            ->when($this->request->has('customer_phone'), function ($query) {
                $query->where('customer_phone', 'like', '%'.$this->request->customer_phone.'%');
            });
    }
}
