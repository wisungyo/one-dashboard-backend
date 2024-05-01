<?php

namespace App\Http\Services\Api\V1;

use Illuminate\Support\Facades\Log;

class PredictionService extends BaseResponse
{
    public function calculatePrediction($year, $month)
    {
        try {
            $data = [];

            return $this->responseSuccess(__('Get prediction value successfully'), 200, $data);
        } catch (\Throwable $th) {
            Log::error($th);

            return $this->responseError(__('Get prediction value failed'), 500, $th->getMessage());
        }
    }
}
