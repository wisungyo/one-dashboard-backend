<?php

namespace App\Http\Services\Api\V1;

use App\Enums\TransactionType;
use App\Http\Resources\Api\V1\IncomeResource;
use App\Models\Income;
use App\Models\Transaction;
use Illuminate\Support\Facades\Log;

class PredictionService extends BaseResponse
{
    const MONTH_PERIODE = 3;

    const ALPHA = 0.1;

    const KEY_TOTAL = 'quantity';

    /**
     * Mean Absolute Percentage Error (MAPE)
     */
    public function mape($actual, $predictions)
    {
        $n = count($actual);
        $sum = 0;
        if ($n == 0) {
            return $sum;
        }
        for ($i = 0; $i < $n; $i++) {
            $sum += abs(($actual[$i][self::KEY_TOTAL] - $predictions[$i][self::KEY_TOTAL]) / $actual[$i][self::KEY_TOTAL]);
        }

        return ($sum / $n) * 100;
    }

    /**
     * Mean Squared Error (MSE)
     */
    public function mse($actual, $predictions)
    {
        $n = count($actual);
        $sum = 0;
        if ($n == 0) {
            return $sum;
        }
        for ($i = 0; $i < $n; $i++) {
            $sum += pow($actual[$i][self::KEY_TOTAL] - $predictions[$i][self::KEY_TOTAL], 2);
        }

        return $sum / $n;
    }

    /**
     * Mean Absolute Deviation (MAD)
     */
    public function mad($actual, $predictions)
    {
        $n = count($actual);
        $sum = 0;
        if ($n == 0) {
            return $sum;
        }
        for ($i = 0; $i < $n; $i++) {
            $sum += abs($actual[$i][self::KEY_TOTAL] - $predictions[$i][self::KEY_TOTAL]);
        }

        return $sum / $n;
    }

    /**
     * Exponential Smoothing
     */
    public function exponentialSmoothing($data, $alpha = self::ALPHA)
    {
        $n = count($data);
        $predictions = [];
        if ($n == 0) {
            return $predictions;
        }
        $data[0]['prediction'] = 0;
        $predictions[0] = $data[0];
        for ($i = 1; $i < $n; $i++) {
            $tmp = $data[$i];
            $tmp['prediction'] = $alpha * $tmp[$i][self::KEY_TOTAL] + (1 - $alpha) * $predictions[$i - 1][self::KEY_TOTAL];
            $predictions[$i] = $tmp;
        }

        return $predictions;
    }

    public function calculatePrediction($year, $month)
    {
        try {
            $data = [];

            // Do exponential smoothing calculation
            $initTimestamp = strtotime("$year-$month-01");
            $firstTimestamp = strtotime('-'.self::MONTH_PERIODE - 1 .' month', $initTimestamp);

            // Sum transaction quantity per month
            $transactions = Transaction::selectRaw('SUM(quantity) as quantity, SUM(total) as amount, YEAR(created_at) as year, MONTH(created_at) as month')
                ->where('type', TransactionType::OUT)
                ->where('created_at', '>=', date('Y-m-d', $firstTimestamp))
                ->where('created_at', '<=', date('Y-m-t', $initTimestamp))
                ->groupBy('year', 'month')
                ->orderBy('year', 'asc')
                ->orderBy('month', 'asc')
                ->get();
            
            $actual = [];
            foreach ($transactions as $transaction) {
                $actual[] = [
                    'year' => $transaction->year,
                    'month' => $transaction->month,
                    'quantity' => $transaction->quantity,
                    'amount' => $transaction->amount,
                ];
            }

            $predictions = $this->exponentialSmoothing($actual);

            // Get prediction value for the year and month
            $predictionValue = 0;
            for ($i = 0; $i < count($predictions); $i++) {
                if ($predictions[$i]['year'] == $year && $predictions[$i]['month'] == $month) {
                    $predictionValue = $predictions[$i];
                    break;
                }
            }

            $data = [
                'year' => $year,
                'month' => $month,
                'month_periode' => self::MONTH_PERIODE,
                'alpha' => self::ALPHA,
                'init_timestamp' => date('Y-m-d', $initTimestamp),
                'first_timestamp' => date('Y-m-d', $firstTimestamp),
                'prediction_value' => $predictionValue,
                'transactions' => $transactions,
                'actual' => $actual,
                'predictions' => $predictions,
                'mape' => $this->mape($actual, $predictions),
                'mse' => $this->mse($actual, $predictions),
                'mad' => $this->mad($actual, $predictions),
            ];

            return $this->responseSuccess(__('Get prediction value successfully'), 200, $data);
        } catch (\Throwable $th) {
            Log::error($th);

            return $this->responseError(__('Get prediction value failed'), 500, $th->getMessage());
        }
    }
}
