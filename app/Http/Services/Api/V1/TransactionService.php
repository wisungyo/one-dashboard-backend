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
use App\Http\Filters\Api\V1\ByTotalItem;
use App\Http\Filters\Api\V1\ByTotalPrice;
use App\Http\Filters\Api\V1\ByTotalQuantity;
use App\Http\Filters\Api\V1\ByType;
use App\Http\Filters\Api\V1\HasItemsInventoryCategoryId;
use App\Http\Filters\Api\V1\HasItemsInventoryId;
use App\Http\Filters\Api\V1\OrderBy;
use App\Http\Resources\Api\V1\TransactionResource;
use App\Models\Inventory;
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
            $query = Transaction::query();
            $piplines = [
                HasItemsInventoryCategoryId::class,
                HasItemsInventoryId::class,
                ByCode::class,
                ByType::class,
                ByTotalItem::class,
                ByTotalQuantity::class,
                ByTotalPrice::class,
                ByCustomerName::class,
                ByCustomerPhone::class,
                ByCustomerAddress::class,
                ByNote::class,
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
            $inventoryIds = array_column($items, 'inventory_id');
            $inventories = Inventory::whereIn('id', $inventoryIds)->get();
            if ($inventories->count() != count($items)) {
                return $this->responseError('Inventory not found.', 404);
            }

            $totalItem = 0;
            $totalQuantity = 0;
            $totalPrice = 0;
            $data['items'] = [];

            foreach ($inventories as $inventory) {
                $item = collect($items)->firstWhere('inventory_id', $inventory->id);
                if ($inventory->quantity <= 0) {
                    return $this->responseError("Out of stock for {$inventory->name}", 400);
                }

                if ($inventory->quantity < $item['quantity']) {
                    return $this->responseError("Stock not enough for {$inventory->name}", 400);
                }

                $totalQuantity += $item['quantity'];
                $totalItem++;
                $totalPrice += $inventory->price * $item['quantity'];

                array_push($data['items'], [
                    'inventory_id' => $inventory->id,
                    'price' => $inventory->price,
                    'quantity' => $item['quantity'],
                    'total' => $inventory->price * $item['quantity'],
                    'note' => isset($item['note']) ? $item['note'] : null,
                ]);
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
                $item = collect($items)->firstWhere('inventory_id', $trxItem->inventory_id);
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
                // Deduct inventories quantity
                foreach ($inventories as $inventory) {
                    $item = collect($items)->firstWhere('inventory_id', $inventory->id);
                    $inventory->quantity -= $item['quantity'];
                    $inventory->save();
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
