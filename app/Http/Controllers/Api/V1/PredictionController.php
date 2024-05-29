<?php

namespace App\Http\Controllers\Api\V1;

use App\Cores\ApiResponse;
use App\Http\Requests\Api\V1\PredictionRequest;
use Facades\App\Http\Services\Api\V1\PredictionService;

class PredictionController extends Controller
{
    use ApiResponse;

    /**
     * @OA\Get(
     *       path="/api/v1/predictions",
     *       summary="Get prediction value ",
     *       description="Endpoint to get prediction ",
     *       tags={"Prediction"},
     *       security={
     *           {"token": {}}
     *       },
     *
     *       @OA\Parameter(
     *           name="year",
     *           in="query",
     *           description="Year",
     *           required=true,
     *       ),
     *       @OA\Parameter(
     *           name="month",
     *           in="query",
     *           description="Month",
     *           required=true,
     *       ),
     *       @OA\Parameter(
     *           name="limit",
     *           in="query",
     *           description="Limit (Default 10)"
     *       ),
     *       @OA\Parameter(
     *           name="page",
     *           in="query",
     *           description="Num Of Page"
     *       ),
     *
     *       @OA\Response(
     *          response=200,
     *          description="Get prediction value successfully",
     *
     *          @OA\JsonContent(
     *
     *              @OA\Property(property="data", type="object", example={}),
     *              @OA\Property(property="pagination", type="object", example={}),
     *          )
     *      ),
     *
     *      @OA\Response(
     *          response=500,
     *          description="Internal server error",
     *
     *          @OA\JsonContent(
     *
     *              @OA\Property(property="status", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Get prediction value failed"),
     *          )
     *      ),
     * )
     */
    public function prediction(PredictionRequest $request)
    {
        $data = $request->validated();
        if (! isset($data['limit'])) {
            $data['limit'] = 10;
        }
        if (! isset($data['page'])) {
            $data['page'] = 1;
        }
        $data = PredictionService::calculatePrediction($data);

        return $this->responseJson(
            $data['status'] ? 'success' : 'error',
            $data['message'],
            $data['data'],
            $data['statusCode']
        );
    }
}
