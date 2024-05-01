<?php

namespace App\Http\Services\Api\V1;

use App\Enums\IncomeType;
use App\Enums\TransactionType;
use App\Http\Resources\Api\V1\IncomeResource;
use App\Models\Income;
use Illuminate\Support\Facades\Log;

class IncomeService extends BaseResponse
{
    public function calculate($transaction = null, $type = IncomeType::ADD, $amount = 0)
    {
        $statusCode = 200;
        try {
            if ((is_null($transaction) && $type == IncomeType::ADD) ||
                (in_array($type, [IncomeType::UPDATE, IncomeType::REMOVE]) && $amount == 0)
            ) {
                Log::error("Can't update income without transaction data.");

                return $this->responseError("Can't update income without transaction data", 400);
            }

            if ($transaction && $transaction->type != TransactionType::OUT) {
                Log::error("Can't update income with transaction type not out.");

                return $this->responseError("Can't update income with transaction type not out", 400);
            }

            $data = [
                'year' => date('Y'),
                'month' => date('m'),
                'created_by' => auth()->id(),
            ];
            if ($type == IncomeType::ADD) {
                $data['amount'] = $transaction->total;
            } else {
                $data['amount'] = $amount;
            }

            $income = Income::where('year', $data['year'])
                ->where('month', $data['month'])
                ->first();

            if ($income) {
                if (in_array($type, [IncomeType::ADD, IncomeType::UPDATE])) {
                    $income->amount += $data['amount'];
                } else {
                    $income->amount -= $data['amount'];
                }
                $income->save();
            } else {
                $statusCode = 201;
                $data['amount'] = $type == IncomeType::ADD ? $data['amount'] : -$data['amount'];
                $income = Income::create($data);
            }

            $resource = new IncomeResource($income);
        } catch (\Throwable $th) {
            Log::error($th);

            return $this->responseError('Failed to update income.', 500, $th->getMessage());
        }

        return $this->responseSuccess('Income has been updated successfully.', $statusCode, $resource);
    }
}
