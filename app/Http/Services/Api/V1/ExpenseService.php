<?php

namespace App\Http\Services\Api\V1;

use App\Enums\ExpenseType;
use App\Http\Filters\Api\V1\ByAmount;
use App\Http\Filters\Api\V1\ByDate;
use App\Http\Filters\Api\V1\ByRangeDate;
use App\Http\Filters\Api\V1\OrderBy;
use App\Http\Resources\Api\V1\ExpenseResource;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ExpenseService extends BaseResponse
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
                    'sort_by' => 'date',
                    'sort' => -1,
                ]);
            }
            $query = Expense::query();
            $piplines = [
                ByDate::class,
                ByAmount::class,
                ByRangeDate::class,
                OrderBy::class,
            ];

            $data = $this->filterPagination($query, $piplines, $request);

            return ExpenseResource::collection($data);
        } catch (\Throwable $th) {
            Log::error($th);

            return $this->responseError(__('Failed get expenses'), $th->getMessage());
        }
    }

    public function getById($id)
    {
        $expense = Expense::find($id);
        if (! $expense) {
            return $this->responseError('Expense not found.', 404);
        }

        $resource = new ExpenseResource($expense);

        return $this->responseSuccess('Expense found.', 200, $resource);
    }

    public function calculate($transaction = null, $type = ExpenseType::ADD, $totalItem = 0, $amount = 0, $quantity = 0)
    {
        $statusCode = 200;
        try {
            if ((is_null($transaction) && $type == ExpenseType::ADD) ||
                (in_array($type, [ExpenseType::UPDATE, ExpenseType::REMOVE]) && $totalItem == 0 && $amount == 0 && $quantity == 0)
            ) {
                Log::error("Can't update expense without transaction data.");

                return $this->responseError("Can't update expense without transaction data", 400);
            }

            $data = [
                'date' => $transaction->created_at->format('Y-m-d') ?? date('Y-m-d'),
                'created_by' => auth()->id(),
            ];
            if ($type == ExpenseType::ADD) {
                $data['total_item'] = $transaction->total_item;
                $data['total_quantity'] = $transaction->total_quantity;
                $data['amount'] = $transaction->total_price;
            } else {
                $data['total_item'] = $totalItem;
                $data['total_quantity'] = $quantity;
                $data['amount'] = $amount;
            }

            $expense = Expense::where('date', $data['date'])->first();

            if ($expense) {
                if (in_array($type, [ExpenseType::ADD, ExpenseType::UPDATE])) {
                    $expense->total_item += $data['total_item'];
                    $expense->total_quantity += $data['total_quantity'];
                    $expense->amount += $data['amount'];
                } else {
                    $expense->total_item -= $data['total_item'];
                    $expense->total_quantity -= $data['total_quantity'];
                    $expense->amount -= $data['amount'];
                }
                $expense->save();
            } else {
                $statusCode = 201;
                $data['total_item'] = $type == ExpenseType::ADD ? $data['total_item'] : -$data['total_item'];
                $data['total_quantity'] = $type == ExpenseType::ADD ? $data['total_quantity'] : -$data['total_quantity'];
                $data['amount'] = $type == ExpenseType::ADD ? $data['amount'] : -$data['amount'];
                $expense = Expense::create($data);
            }

            $resource = new ExpenseResource($expense);
        } catch (\Throwable $th) {
            Log::error($th);

            return $this->responseError('Failed to update expense.', 500, $th->getMessage());
        }

        return $this->responseSuccess('Expense has been updated successfully.', $statusCode, $resource);
    }
}
