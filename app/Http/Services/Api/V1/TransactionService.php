<?php

namespace App\Http\Services\Api\V1;

use App\Enums\ExpenseType;
use App\Enums\IncomeType;
use App\Enums\TransactionType;
use App\Http\Filters\Api\V1\ByCode;
use App\Http\Filters\Api\V1\ByCustomerAddress;
use App\Http\Filters\Api\V1\ByCustomerName;
use App\Http\Filters\Api\V1\ByCustomerPhone;
use App\Http\Filters\Api\V1\ByNote;
use App\Http\Filters\Api\V1\ByRangeCreatedAt;
use App\Http\Filters\Api\V1\ByTotalItem;
use App\Http\Filters\Api\V1\ByTotalPrice;
use App\Http\Filters\Api\V1\ByTotalQuantity;
use App\Http\Filters\Api\V1\ByType;
use App\Http\Filters\Api\V1\HasItemsProductCategoryId;
use App\Http\Filters\Api\V1\HasItemsProductId;
use App\Http\Filters\Api\V1\OrderBy;
use App\Http\Resources\Api\V1\TransactionResource;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Facades\App\Http\Services\Api\V1\ExpenseService;
use Facades\App\Http\Services\Api\V1\IncomeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransactionService extends BaseResponse
{
    public function list(Request $request)
    {
        try {
            // Formatting start and end date
            if ($request->has('start_date')) {
                $request->merge(['start_date' => date('Y-m-d', strtotime($request->start_date))]);
            }
            if ($request->has('end_date')) {
                $request->merge(['end_date' => date('Y-m-d', strtotime($request->end_date))]);
            }
            if (! ($request->has('sort_by') && $request->has('sort'))) {
                $request->merge([
                    'sort_by' => 'created_at',
                    'sort' => -1,
                ]);
            }
            $query = Transaction::query();
            $piplines = [
                HasItemsProductCategoryId::class,
                HasItemsProductId::class,
                ByCode::class,
                ByType::class,
                ByTotalItem::class,
                ByTotalQuantity::class,
                ByTotalPrice::class,
                ByCustomerName::class,
                ByCustomerPhone::class,
                ByCustomerAddress::class,
                ByNote::class,
                ByRangeCreatedAt::class,
                OrderBy::class,
            ];

            $data = $this->filterPagination($query, $piplines, $request);

            return TransactionResource::collection($data);
        } catch (\Throwable $th) {
            Log::error($th);

            return $this->responseError(__('Failed get transactions'), $th->getMessage());
        }
    }

    public function store($data)
    {
        DB::beginTransaction();
        try {
            $items = $data['items'];
            $productIds = array_column($items, 'product_id');
            $products = Product::whereIn('id', $productIds)->get();
            if ($products->count() != count($items)) {
                return $this->responseError('Product not found.', 404);
            }

            $totalItem = 0;
            $totalQuantity = 0;
            $totalPrice = 0;
            $data['items'] = [];

            foreach ($products as $product) {
                $item = collect($items)->firstWhere('product_id', $product->id);
                if ($product->quantity <= 0) {
                    return $this->responseError("Out of stock for {$product->name}", 400);
                }

                if ($product->quantity < $item['quantity']) {
                    return $this->responseError("Stock not enough for {$product->name}", 400);
                }

                $totalQuantity += $item['quantity'];
                $totalItem++;
                $totalPrice += $product->price * $item['quantity'];

                $data['items'][] = [
                    'product_id' => $product->id,
                    'price' => $product->price,
                    'quantity' => $item['quantity'],
                    'total' => $product->price * $item['quantity'],
                    'note' => isset($item['note']) ? $item['note'] : null,
                    'created_at' => $data['created_at'] ?? now(),
                    'updated_at' => now(),
                ];
            }

            $data['total_item'] = $totalItem;
            $data['total_quantity'] = $totalQuantity;
            $data['total_price'] = $totalPrice;
            $data['code'] = 'TRX-'.$data['type']->value.'-'.date('YmdHis');
            $data['created_by'] = auth()->id();

            // Create transaction with the items
            $transaction = Transaction::create($data);
            for ($i = 0; $i < count($data['items']); $i++) {
                $data['items'][$i]['transaction_id'] = $transaction->id;
            }
            TransactionItem::insert($data['items']);

            // Add image for transaction
            if (isset($data['image'])) {
                $transaction->images()->create([
                    'type' => 'transaction',
                    'size' => $data['image']->getSize(),
                    'mime_type' => $data['image']->getMimeType(),
                    'file_name' => $data['image']->getClientOriginalName(),
                    'path' => $data['image']->store('images/transaction'),
                    'height' => 0,
                    'width' => 0,
                ]);
            }

            // Add image for transaction items
            $trxItems = TransactionItem::where('transaction_id', $transaction->id)->get();
            foreach ($trxItems as $trxItem) {
                $item = collect($items)->firstWhere('product_id', $trxItem->product_id);
                if (! isset($item['image'])) {
                    continue;
                }
                $trxItem->images()->delete();
                $trxItem->images()->create([
                    'type' => 'transaction_item',
                    'size' => $item['image']->getSize(),
                    'mime_type' => $item['image']->getMimeType(),
                    'file_name' => $item['image']->getClientOriginalName(),
                    'path' => $item['image']->store('images/transaction_item'),
                    'height' => 0,
                    'width' => 0,
                ]);
            }

            if ($data['type'] == TransactionType::IN) {
                // Add expense
                $expense = ExpenseService::calculate($transaction, ExpenseType::ADD);
                if (! $expense['status']) {
                    DB::rollBack();
                    Log::error($expense['message']);

                    return $this->responseError($expense['message'], $expense['statusCode'], $expense['data']['errors']);
                }
            } elseif ($data['type'] == TransactionType::OUT) {
                // Deduct products quantity
                foreach ($products as $product) {
                    $item = collect($items)->firstWhere('product_id', $product->id);
                    $product->quantity -= $item['quantity'];
                    $product->save();
                }

                // Add Income
                $income = IncomeService::calculate($transaction, IncomeType::ADD);
                if (! $income['status']) {
                    DB::rollBack();
                    Log::error($income['message']);

                    return $this->responseError($income['message'], $income['statusCode'], $income['data']['errors']);
                }
            } else {
                DB::rollBack();

                return $this->responseError('Transaction type not valid.', 400);
            }

            $resource = new TransactionResource($transaction);

            DB::commit();
        } catch (\Exception $th) {
            DB::rollBack();
            Log::error($th);

            return $this->responseError('Failed to create transaction.', 500, $th->getMessage());
        }

        return $this->responseSuccess('Transaction has been created successfully.', 201, $resource);
    }

    public function getById($id)
    {
        $transaction = Transaction::find($id);
        if (! $transaction) {
            return $this->responseError('Transaction not found.', 404);
        }

        $resource = new TransactionResource($transaction);

        return $this->responseSuccess('Transaction found.', 200, $resource);
    }
}
