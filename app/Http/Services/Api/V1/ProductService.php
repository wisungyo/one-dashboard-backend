<?php

namespace App\Http\Services\Api\V1;

use App\Enums\ExpenseType;
use App\Enums\TransactionType;
use App\Http\Filters\Api\V1\ByCategoryId;
use App\Http\Filters\Api\V1\ByCode;
use App\Http\Filters\Api\V1\ByDescription;
use App\Http\Filters\Api\V1\ByName;
use App\Http\Filters\Api\V1\ByPrice;
use App\Http\Filters\Api\V1\ByQuantity;
use App\Http\Filters\Api\V1\OrderBy;
use App\Http\Resources\Api\V1\ProductResource;
use App\Models\Product;
use App\Models\TransactionItem;
use Facades\App\Http\Services\Api\V1\ExpenseService;
use Facades\App\Http\Services\Api\V1\TransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProductService extends BaseResponse
{
    public function list(Request $request)
    {
        try {
            $query = Product::query();
            $piplines = [
                ByCategoryId::class,
                ByCode::class,
                ByName::class,
                ByDescription::class,
                ByPrice::class,
                ByQuantity::class,
                OrderBy::class,
            ];

            $data = $this->filterPagination($query, $piplines, $request);

            return ProductResource::collection($data);
        } catch (\Throwable $th) {
            Log::error($th);

            return $this->responseError(__('Failed get products'), $th->getMessage());
        }
    }

    public function store($data)
    {
        DB::beginTransaction();
        try {
            $data['created_by'] = auth()->id();

            $product = Product::create($data);

            // Add image
            if (isset($data['image'])) {
                $product->images()->create([
                    'type' => 'product',
                    'size' => $data['image']->getSize(),
                    'mime_type' => $data['image']->getMimeType(),
                    'file_name' => $data['image']->getClientOriginalName(),
                    'path' => $data['image']->store('images/product'),
                    'height' => 0,
                    'width' => 0,
                ]);
            }

            // Add in transaction
            $trxData = [
                'type' => TransactionType::IN,
                'total_price' => $product->price,
                'total_quantity' => $product->quantity,
                'note' => 'Create product', // 'Add product
                'created_by' => auth()->id(),
                'items' => [
                    [
                        'product_id' => $product->id,
                        'price' => $product->price,
                        'quantity' => $product->quantity,
                        'total' => $product->price * $product->quantity,
                    ],
                ],
            ];
            $trxResp = TransactionService::store($trxData);
            if (isset($trxResp['status']) && ! $trxResp['status']) {
                DB::rollBack();
                Log::error($trxResp['message']);

                return $this->responseError($trxResp['message'], $trxResp['statusCode'], $trxResp['data']['errors']);
            }

            $resource = new ProductResource($product);

            DB::commit();
        } catch (\Exception $th) {
            DB::rollBack();
            Log::error($th);

            return $this->responseError('Failed to create product.', 500, $th->getMessage());
        }

        return $this->responseSuccess('Product has been created successfully.', 201, $resource);
    }

    public function getById($id)
    {
        $product = Product::find($id);
        if (! $product) {
            return $this->responseError('Product not found.', 404);
        }

        $resource = new ProductResource($product);

        return $this->responseSuccess('Product found.', 200, $resource);
    }

    public function update($id, $data)
    {
        DB::beginTransaction();
        try {
            $product = Product::find($id);
            if (! $product) {
                return $this->responseError('Product not found.', 404);
            }
            $prevQuantity = $product->quantity;
            $prevPrice = $product->price;
            $prevTotalAmount = $prevQuantity * $prevPrice;
            $product->update($data);
            $diffQuantity = $data['quantity'] - $prevQuantity;
            $diffPrice = $data['price'] - $prevPrice;
            $diffTotalAmount = ($data['price'] * $data['quantity']) - $prevTotalAmount;

            // Update image
            if (isset($data['image'])) {
                $product->images()->delete();
                $product->images()->create([
                    'type' => 'product',
                    'size' => $data['image']->getSize(),
                    'mime_type' => $data['image']->getMimeType(),
                    'file_name' => $data['image']->getClientOriginalName(),
                    'path' => Storage::putFile('images/product', $data['image']),
                    'height' => 0,
                    'width' => 0,
                ]);
            }

            // Add in transaction to deduct the previous total amount
            $trxData = [
                'type' => TransactionType::IN,
                'total_price' => $diffTotalAmount,
                'total_quantity' => $diffQuantity,
                'note' => 'Update product', // 'Update product
                'created_by' => auth()->id(),
                'items' => [
                    [
                        'product_id' => $product->id,
                        'price' => $diffPrice,
                        'quantity' => $diffQuantity,
                        'total' => $diffTotalAmount,
                    ],
                ],
            ];
            $trxResp = TransactionService::store($trxData);
            if (isset($trxResp['status']) && ! $trxResp['status']) {
                DB::rollBack();
                Log::error($trxResp['message']);

                return $this->responseError($trxResp['message'], $trxResp['statusCode'], $trxResp['data']['errors']);
            }

            $resource = new ProductResource($product);

            DB::commit();
        } catch (\Exception $th) {
            DB::rollBack();
            Log::error($th);

            return $this->responseError('Failed to update product.', 500, $th->getMessage());
        }

        return $this->responseSuccess('Product has been updated successfully.', 200, $resource);
    }

    public function delete($id)
    {
        DB::beginTransaction();
        try {
            $product = Product::find($id);
            if (! $product) {
                return $this->responseError('Product not found.', 404);
            }

            $product->delete();

            // Recalculate expense
            $totalItem = 1;
            $totalRemainQuantity = $product->quantity;
            $totalRemainAmount = $product->price * $product->quantity;
            $expense = ExpenseService::calculate(null, ExpenseType::REMOVE, $totalItem, $totalRemainQuantity, $totalRemainAmount);
            if (! $expense['status']) {
                DB::rollBack();
                Log::error($expense['message']);

                return $this->responseError($expense['message'], $expense['statusCode'], $expense['data']['errors']);
            }

            DB::commit();
        } catch (\Exception $th) {
            DB::rollBack();
            Log::error($th);

            return $this->responseError('Failed to delete product.', 500, $th->getMessage());
        }

        return $this->responseSuccess('Product has been deleted successfully.', 200);
    }

    public function mostSold($request, $limit = 5)
    {
        try {
            $transactionItems = TransactionItem::selectRaw('product_id, sum(quantity) as total_quantity, sum(total) as total_price')
                ->leftJoin('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
                ->where('transactions.type', TransactionType::OUT)
                ->groupBy('product_id')
                ->orderBy('total_quantity', 'desc')
                ->limit($limit)
                ->get()
                ->toArray();

            // Get all product based on transaction items
            $productIds = array_column($transactionItems, 'product_id');
            $products = Product::with('category')->whereIn('id', $productIds)->get();
            $productResources = [];

            foreach ($products as $product) {
                $productResources[$product->id] = new ProductResource($product);
            }

            $data = [];
            foreach ($transactionItems as $transactionItem) {
                $data[] = [
                    'product' => $productResources[$transactionItem['product_id']] ?? null,
                    'total_sold' => $transactionItem['total_quantity'],
                    'total_price' => $transactionItem['total_price'],
                ];
            }

            return $this->responseSuccess('Most sold products.', 200, $data);
        } catch (\Throwable $th) {
            Log::error($th);

            return $this->responseError(__('Failed get most sold products'), 500, $th->getMessage());
        }
    }
}
