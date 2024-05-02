<?php

namespace App\Http\Services\Api\V1;

use App\Http\Resources\Api\V1\IncomeResource;
use App\Models\Transaction;
use Illuminate\Support\Facades\Log;

class PredictionService extends BaseResponse
{
    const MONTH_PERIODE = 3;

    const ALPHA = 0.1;

    public function calculatePrediction($year, $month)
    {
        try {
            $data = [];

            // Do exponential smoothing calculation
            $initTimestamp = strtotime("$year-$month-01");
            $latestTimestamp = strtotime('-'.self::MONTH_PERIODE - 1 .' month', $initTimestamp);

            // Get income data
            $incomes = Transaction::where('type', 'income')
                ->where('created_at', '<', date('Y-m-d', $latestTimestamp))
                ->get();
            $incomeResources = IncomeResource::collection($incomes);

            // Calculate prediction value
            $prediction = 0;
            $monthPrediction = [];
            foreach ($incomeResources as $income) {
                $prediction = self::ALPHA * $income->amount + (1 - self::ALPHA) * $prediction;
                $monthPrediction[] = [
                    'month' => $income->month,
                    'year' => $income->year,
                    'amount' => $income->amount,
                    'predicton' => $prediction,
                ];
            }

            $data = [
                'month_period' => self::MONTH_PERIODE,
                'init_timestamp' => date('Y-m-d', $initTimestamp),
                'latest_timestamp' => date('Y-m-d', $latestTimestamp),
                'prediction' => $prediction,
                'incomes' => $incomeResources,
                'month_prediction' => $monthPrediction,
            ];

            return $this->responseSuccess(__('Get prediction value successfully'), 200, $data);
        } catch (\Throwable $th) {
            Log::error($th);

            return $this->responseError(__('Get prediction value failed'), 500, $th->getMessage());
        }
    }
}
