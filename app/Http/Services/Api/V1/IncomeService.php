<?php

namespace App\Http\Services\Api\V1;

use App\Enums\IncomeType;
use App\Enums\TransactionType;
use App\Http\Filters\Api\V1\ByAmount;
use App\Http\Filters\Api\V1\ByDate;
use App\Http\Filters\Api\V1\ByRangeDate;
use App\Http\Filters\Api\V1\OrderBy;
use App\Http\Resources\Api\V1\IncomeResource;
use App\Models\Income;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class IncomeService extends BaseResponse
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
            $query = Income::query();
            $piplines = [
                ByDate::class,
                ByAmount::class,
                ByRangeDate::class,
                OrderBy::class,
            ];

            $data = $this->filterPagination($query, $piplines, $request);

            return IncomeResource::collection($data);
        } catch (\Throwable $th) {
            Log::error($th);

            return $this->responseError(__('Failed get incomes'), $th->getMessage());
        }
    }

    public function getById($id)
    {
        $income = Income::find($id);
        if (! $income) {
            return $this->responseError('Income not found.', 404);
        }

        $resource = new IncomeResource($income);

        return $this->responseSuccess('Income found.', 200, $resource);
    }

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
                'date' => date('Y-m-d'),
                'created_by' => auth()->id(),
            ];
            if ($type == IncomeType::ADD) {
                $data['amount'] = $transaction->total_price;
            } else {
                $data['amount'] = $amount;
            }

            $income = Income::where('date', $data['date'])->first();

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
