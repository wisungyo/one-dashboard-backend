<?php

namespace App\Http\Filters\Api\V1;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class HasItemsProductCategoryId
{
    public function __construct(protected Request $request)
    {
        //
    }

    public function handle(Builder $builder, \Closure $next)
    {
        $request = $this->request;

        return $next($builder)
            ->when($request->has('category_id'), function ($query) use ($request) {
                $query->whereHas('items', function ($query) use ($request) {
                    $query->whereHas('product', function ($query) use ($request) {
                        $query->where('category_id', $request->category_id);
                    });
                });
            });
    }
}
