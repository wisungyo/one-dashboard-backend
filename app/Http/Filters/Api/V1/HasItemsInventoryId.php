<?php

namespace App\Http\Filters\Api\V1;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class HasItemsInventoryId
{
    public function __construct(protected Request $request)
    {
        //
    }

    public function handle(Builder $builder, \Closure $next)
    {
        $request = $this->request;

        return $next($builder)
            ->when($request->has('inventory_id'), function ($query) use ($request) {
                $query->whereHas('items', function ($query) use ($request) {
                    $query->where('inventory_id', $request->inventory_id);
                });
            });
    }
}
