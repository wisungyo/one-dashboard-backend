<?php

namespace App\Http\Services\Api\V1;

use App\Enums\ExpenseType;
use App\Enums\TransactionType;
use App\Http\Filters\Api\V1\ByCode;
use App\Http\Filters\Api\V1\ByDescription;
use App\Http\Filters\Api\V1\ByName;
use App\Http\Filters\Api\V1\ByPrice;
use App\Http\Filters\Api\V1\ByQuantity;
use App\Http\Filters\Api\V1\OrderBy;
use App\Http\Resources\Api\V1\InventoryResource;
use App\Models\Inventory;
use Facades\App\Http\Services\Api\V1\ExpenseService;
use Facades\App\Http\Services\Api\V1\TransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class InventoryService extends BaseResponse
{
    public function list(Request $request)
    {
        try {
            $query = Inventory::query();
            $piplines = [
                ByCode::class,
                ByName::class,
                ByDescription::class,
                ByPrice::class,
                ByQuantity::class,
                OrderBy::class,
            ];

            $data = $this->filterPagination($query, $piplines, $request);

            return InventoryResource::collection($data);
        } catch (\Throwable $th) {
            Log::error($th);

            return $this->responseError(__('Failed get inventories'), $th->getMessage());
        }
    }

    public function store($data)
    {
        DB::beginTransaction();
        try {
            $data['created_by'] = auth()->id();

            $inventory = Inventory::create($data);

            // Add image
            if (isset($data['image'])) {
                $inventory->images()->create([
                    'type' => 'inventory',
                    'size' => $data['image']->getSize(),
                    'mime_type' => $data['image']->getMimeType(),
                    'file_name' => $data['image']->getClientOriginalName(),
                    'path' => $data['image']->store('images/inventory'),
                    'height' => 0,
                    'width' => 0,
                ]);
            }

            // Add in transaction
            $trxData = [
                'inventory_id' => $inventory->id,
                'type' => TransactionType::IN,
                'price' => $inventory->price,
                'quantity' => $inventory->quantity,
                'note' => 'Create inventory', // 'Add inventory
                'created_by' => auth()->id(),
            ];
            $trxResp = TransactionService::store($trxData);
            if (isset($trxResp['status']) && ! $trxResp['status']) {
                DB::rollBack();
                Log::error($trxResp['message']);

                return $this->responseError($trxResp['message'], $trxResp['statusCode'], $trxResp['data']['errors']);
            }

            $resource = new InventoryResource($inventory);

            DB::commit();
        } catch (\Exception $th) {
            DB::rollBack();
            Log::error($th);

            return $this->responseError('Failed to create inventory.', 500, $th->getMessage());
        }

        return $this->responseSuccess('Inventory has been created successfully.', 201, $resource);
    }

    public function getById($id)
    {
        $inventory = Inventory::find($id);
        if (! $inventory) {
            return $this->responseError('Inventory not found.', 404);
        }

        $resource = new InventoryResource($inventory);

        return $this->responseSuccess('Inventory found.', 200, $resource);
    }

    public function update($id, $data)
    {
        DB::beginTransaction();
        try {
            $inventory = Inventory::find($id);
            if (! $inventory) {
                return $this->responseError('Inventory not found.', 404);
            }
            $prevTotalAmount = $inventory->price * $inventory->quantity;
            $inventory->update($data);
            $diffTotalAmount = ($inventory->price * $inventory->quantity) - $prevTotalAmount;

            // Update image
            if (isset($data['image'])) {
                $inventory->images()->delete();
                $inventory->images()->create([
                    'type' => 'inventory',
                    'size' => $data['image']->getSize(),
                    'mime_type' => $data['image']->getMimeType(),
                    'file_name' => $data['image']->getClientOriginalName(),
                    'path' => Storage::putFile('images/inventory', $data['image']),
                    'height' => 0,
                    'width' => 0,
                ]);
            }

            // Add in transaction to deduct the previous total amount
            $trxData = [
                'inventory_id' => $inventory->id,
                'type' => TransactionType::IN,
                'price' => $diffTotalAmount,
                'quantity' => $data['quantity'],
                'note' => 'Update inventory',
                'created_by' => auth()->id(),
            ];
            $trxResp = TransactionService::store($trxData);
            if (isset($trxResp['status']) && ! $trxResp['status']) {
                DB::rollBack();
                Log::error($trxResp['message']);

                return $this->responseError($trxResp['message'], $trxResp['statusCode'], $trxResp['data']['errors']);
            }

            $resource = new InventoryResource($inventory);

            DB::commit();
        } catch (\Exception $th) {
            DB::rollBack();
            Log::error($th);

            return $this->responseError('Failed to update inventory.', 500, $th->getMessage());
        }

        return $this->responseSuccess('Inventory has been updated successfully.', 200, $resource);
    }

    public function delete($id)
    {
        DB::beginTransaction();
        try {
            $inventory = Inventory::find($id);
            if (! $inventory) {
                return $this->responseError('Inventory not found.', 404);
            }

            $inventory->delete();

            // Recalculate expense
            $totalRemain = $inventory->price * $inventory->quantity;
            $expense = ExpenseService::calculate(null, ExpenseType::REMOVE, $totalRemain);
            if (! $expense['status']) {
                DB::rollBack();
                Log::error($expense['message']);

                return $this->responseError($expense['message'], $expense['statusCode'], $expense['data']['errors']);
            }

            DB::commit();
        } catch (\Exception $th) {
            DB::rollBack();
            Log::error($th);

            return $this->responseError('Failed to delete inventory.', 500, $th->getMessage());
        }

        return $this->responseSuccess('Inventory has been deleted successfully.', 200);
    }
}
