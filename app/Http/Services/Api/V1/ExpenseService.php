<?php

namespace App\Http\Services\Api\V1;

use App\Enums\ExpenseType;
use App\Http\Resources\Api\V1\ExpenseResource;
use App\Models\Expense;
use Illuminate\Support\Facades\Log;

class ExpenseService extends BaseResponse
{
    public function calculate($transaction = null, $type = ExpenseType::ADD, $amount = 0)
    {
        $statusCode = 200;
        try {
            if ((is_null($transaction) && $type == ExpenseType::ADD) ||
                (in_array($type, [ExpenseType::UPDATE, ExpenseType::REMOVE]) && $amount == 0)
            ) {
                Log::error("Can't update expense without transaction data.");

                return $this->responseError("Can't update expense without transaction data", 400);
            }

            $data = [
                'date' => date('Y-m-d'),
                'created_by' => auth()->id(),
            ];
            if ($type == ExpenseType::ADD) {
                $data['amount'] = $transaction->total_price;
            } else {
                $data['amount'] = $amount;
            }

            $expense = Expense::where('date', $data['date'])->first();

            if ($expense) {
                if (in_array($type, [ExpenseType::ADD, ExpenseType::UPDATE])) {
                    $expense->amount += $data['amount'];
                } else {
                    $expense->amount -= $data['amount'];
                }
                $expense->save();
            } else {
                $statusCode = 201;
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
