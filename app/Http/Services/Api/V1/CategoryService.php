<?php

namespace App\Http\Services\Api\V1;

use App\Http\Filters\Api\V1\ByDescription;
use App\Http\Filters\Api\V1\ByName;
use App\Http\Filters\Api\V1\OrderBy;
use App\Http\Resources\Api\V1\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CategoryService extends BaseResponse
{
    public function list(Request $request)
    {
        try {
            $query = Category::query();
            // set default order by and limit
            $request->merge([
                'sort_by' => $request->sort_by ?? 'id',
                'sort' => $request->sort ?? 'asc',
                'limit' => $request->limit ?? 20,
            ]);
            $piplines = [
                ByName::class,
                ByDescription::class,
                OrderBy::class,
            ];

            $data = $this->filterPagination($query, $piplines, $request);

            return CategoryResource::collection($data);
        } catch (\Throwable $th) {
            Log::error($th);

            return $this->responseError(__('Failed get categories'), $th->getMessage());
        }
    }

    public function store($data)
    {
        DB::beginTransaction();
        try {
            $data['created_by'] = auth()->id();

            $category = Category::create($data);

            $resource = new CategoryResource($category);

            DB::commit();
        } catch (\Exception $th) {
            DB::rollBack();
            Log::error($th);

            return $this->responseError('Failed to create category.', 500, $th->getMessage());
        }

        return $this->responseSuccess('Category has been created successfully.', 201, $resource);
    }

    public function getById($id)
    {
        $category = Category::find($id);
        if (! $category) {
            return $this->responseError('Category not found.', 404);
        }

        $resource = new CategoryResource($category);

        return $this->responseSuccess('Category found.', 200, $resource);
    }

    public function update($id, $data)
    {
        DB::beginTransaction();
        try {
            $category = Category::find($id);
            if (! $category) {
                return $this->responseError('Category not found.', 404);
            }
            $category->update($data);

            $resource = new CategoryResource($category);

            DB::commit();
        } catch (\Exception $th) {
            DB::rollBack();
            Log::error($th);

            return $this->responseError('Failed to update category.', 500, $th->getMessage());
        }

        return $this->responseSuccess('Category has been updated successfully.', 200, $resource);
    }

    public function delete($id)
    {
        DB::beginTransaction();
        try {
            $category = Category::find($id);
            if (! $category) {
                return $this->responseError('Category not found.', 404);
            }

            $category->delete();

            DB::commit();
        } catch (\Exception $th) {
            DB::rollBack();
            Log::error($th);

            return $this->responseError('Failed to delete category.', 500, $th->getMessage());
        }

        return $this->responseSuccess('Category has been deleted successfully.', 200);
    }

    public function mostSold($request)
    {
        try {
            $categories = Category::with(['products.transactionItems'])
                ->get()
                ->map(function ($category) {
                    $totalSold = 0;
                    $category->products->each(function ($product) use (&$totalSold) {
                        $product->transactionItems->each(function ($transactionItem) use (&$totalSold) {
                            $totalSold += $transactionItem->quantity;
                        });
                    });

                    return [
                        'id' => $category->id,
                        'name' => $category->name,
                        'description' => $category->description,
                        'total_sold' => $totalSold,
                    ];
                })
                ->sortByDesc('total_sold');

            $totalAllSold = $categories->sum('total_sold');
            $totalCategories = $categories->count();
            $data = [
                'total_categories' => $totalCategories,
                'total_sold' => $totalAllSold,
                'items' => [],
            ];
            // Mapping data items with the percentage of sold
            $categories->each(function ($category) use ($totalAllSold, &$data) {
                $percentage = $category['total_sold'] / $totalAllSold * 100;
                $data['items'][] = [
                    'id' => $category['id'],
                    'name' => $category['name'],
                    'description' => $category['description'],
                    'total_sold' => $category['total_sold'],
                    'percentage' => round($percentage, 2),
                ];
            });

            return $this->responseSuccess(__('Get most sold categories successfully'), 200, $data);
        } catch (\Throwable $th) {
            Log::error($th);

            return $this->responseError(__('Failed get most sold categories'), 500, $th->getMessage());
        }
    }
}
