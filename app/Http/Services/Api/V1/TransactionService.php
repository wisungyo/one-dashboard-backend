<?php

namespace App\Http\Services\Api\V1;

use App\Enums\ExpenseType;
use App\Enums\IncomeType;
use App\Enums\TransactionType;
use App\Http\Filters\Api\V1\ByCode;
use App\Http\Filters\Api\V1\ByInventoryId;
use App\Http\Filters\Api\V1\ByPrice;
use App\Http\Filters\Api\V1\ByQuantity;
use App\Http\Filters\Api\V1\ByTotal;
use App\Http\Filters\Api\V1\ByType;
use App\Http\Filters\Api\V1\OrderBy;
use App\Http\Resources\Api\V1\TransactionResource;
use App\Models\Inventory;
use App\Models\Transaction;
use Facades\App\Http\Services\Api\V1\ExpenseService;
use Facades\App\Http\Services\Api\V1\IncomeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TransactionService extends BaseResponse
{
    public function list(Request $request)
    {
        try {
            $query = Transaction::query();
            $piplines = [
                ByInventoryId::class,
                ByCode::class,
                ByType::class,
                ByPrice::class,
                ByQuantity::class,
                ByTotal::class,
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
            $inventory = Inventory::find($data['inventory_id']);
            if (! $inventory) {
                return $this->responseError('Inventory not found.', 404);
            }

            if ($inventory->quantity <= 0) {
                return $this->responseError('Out of stock.', 400);
            }

            if ($inventory->quantity < $data['quantity']) {
                return $this->responseError('Stock not enough.', 400);
            }

            $data['price'] = $inventory->price;
            $data['total'] = $data['price'] * $data['quantity'];
            $data['code'] = 'TRX-'.$data['inventory_id'].'-'.$data['type']->value.'-'.date('YmdHis');
            $data['created_by'] = auth()->id();

            $transaction = Transaction::create($data);

            // Add image
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

            if ($data['type'] == TransactionType::IN) {
                // Add expense
                $expense = ExpenseService::calculate($transaction, ExpenseType::ADD);
                if (! $expense['status']) {
                    DB::rollBack();
                    Log::error($expense['message'], $expense['data']['errors']);

                    return $this->responseError($expense['message'], $expense['statusCode'], $expense['data']['errors']);
                }
            } elseif ($data['type'] == TransactionType::OUT) {
                // Deduct inventory quantity
                $inventory->quantity -= $data['quantity'];
                $inventory->save();

                // Add Income
                $income = IncomeService::calculate($transaction, IncomeType::ADD);
                if (! $income['status']) {
                    DB::rollBack();
                    Log::error($income['message'], $income['data']['errors']);

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

    public function update($id, $data)
    {
        DB::beginTransaction();
        try {
            $transaction = Transaction::find($id);
            if (! $transaction) {
                return $this->responseError('Transaction not found.', 404);
            }

            if ($transaction->type != TransactionType::OUT) {
                return $this->responseError('Transaction type not out.', 400);
            }

            $inventory = $transaction->inventory;
            $diffQuantity = $data['quantity'] - $transaction->quantity;
            if ($inventory->quantity < $diffQuantity) {
                return $this->responseError('Stock not enough.', 400);
            }

            $prevTotalAmount = $transaction->total;
            $transaction->update($data);
            $diffTotalAmount = $transaction->total - $prevTotalAmount;

            // Update image
            if (isset($data['image'])) {
                $transaction->images()->delete();
                $transaction->images()->create([
                    'type' => 'transaction',
                    'size' => $data['image']->getSize(),
                    'mime_type' => $data['image']->getMimeType(),
                    'file_name' => $data['image']->getClientOriginalName(),
                    'path' => Storage::putFile('images/transaction', $data['image']),
                    'height' => 0,
                    'width' => 0,
                ]);
            }

            // Add out transaction to deduct the previous total amount
            $trxData = [
                'inventory_id' => $transaction->inventory_id,
                'type' => TransactionType::OUT,
                'price' => $diffTotalAmount,
                'quantity' => $diffQuantity,
                'note' => 'Update transaction',
            ];
            $trxResp = $this->store($trxData);
            if (isset($trxResp['status']) && ! $trxResp['status']) {
                DB::rollBack();
                Log::error($trxResp['message']);

                return $this->responseError($trxResp['message'], $trxResp['statusCode'], $trxResp['data']['errors']);
            }

            // // Recalculate income
            // $income = IncomeService::calculate($transaction, IncomeType::UPDATE, $diffTotalAmount);
            // if (! $income['status']) {
            //     DB::rollBack();
            //     Log::error($income['message'], $income['data']['errors']);

            //     return $this->responseError($income['message'], $income['statusCode'], $income['data']['errors']);
            // }

            $resource = new TransactionResource($transaction);

            DB::commit();
        } catch (\Exception $th) {
            DB::rollBack();
            Log::error($th);

            return $this->responseError('Failed to update transaction.', 500, $th->getMessage());
        }

        return $this->responseSuccess('Transaction has been updated successfully.', 200, $resource);
    }

    public function delete($id)
    {
        DB::beginTransaction();
        try {
            $transaction = Transaction::find($id);
            if (! $transaction) {
                return $this->responseError('Transaction not found.', 404);
            }

            if ($transaction->type != TransactionType::OUT) {
                return $this->responseError('Transaction type not out.', 400);
            }

            // Recalculate income
            $income = IncomeService::calculate(null, IncomeType::REMOVE, $transaction->total);
            if (! $income['status']) {
                DB::rollBack();
                Log::error($income['message'], $income['data']['errors']);

                return $this->responseError($income['message'], $income['statusCode'], $income['data']['errors']);
            }

            $transaction->delete();

            DB::commit();
        } catch (\Exception $th) {
            DB::rollBack();
            Log::error($th);

            return $this->responseError('Failed to delete transaction.', 500, $th->getMessage());
        }

        return $this->responseSuccess('Transaction has been deleted successfully.', 200);
    }
}
