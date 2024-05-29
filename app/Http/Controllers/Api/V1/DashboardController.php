<?php

namespace App\Http\Controllers\Api\V1;

use App\Cores\ApiResponse;
use Facades\App\Http\Services\Api\V1\CategoryService;
use Facades\App\Http\Services\Api\V1\IncomeService;
use Facades\App\Http\Services\Api\V1\ProductService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use ApiResponse;

    /**
     * @OA\Get(
     *       path="/api/v1/dashboard/sales-summary",
     *       summary="Get sales summary",
     *       description="Endpoint to get sales summary",
     *       tags={"Dashboard"},
     *       security={
     *           {"token": {}}
     *       },
     *
     *       @OA\Parameter(
     *           name="start_date",
     *           in="query",
     *           description="Start Date",
     *           required=true
     *       ),
     *       @OA\Parameter(
     *           name="end_date",
     *           in="query",
     *           description="End Date",
     *           required=true
     *       ),
     *
     *       @OA\Response(
     *          response=200,
     *          description="Get sales summary successfully",
     *
     *          @OA\JsonContent(
     *
     *              @OA\Property(property="status", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Get sales summary successfully"),
     *              @OA\Property(property="data", type="object", example={}),
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
     *              @OA\Property(property="message", type="string", example="Internal server error"),
     *          )
     *      ),
     * )
     */
    public function salesSummary(Request $request)
    {
        // Set default range date to 30 days
        $request->merge([
            'start_date' => $request->start_date ?? now()->subDays(30)->format('Y-m-d'),
            'end_date' => $request->end_date ?? now()->format('Y-m-d'),
            'sort_by' => $request->sort_by ?? 'date',
            'sort' => $request->sort ?? 1,
        ]);

        $data = IncomeService::list($request, false);

        return $this->responseJson(
            $data['status'] ? 'success' : 'error',
            $data['message'],
            $data['data'],
            $data['statusCode']
        );
    }

    /**
     * @OA\Get(
     *       path="/api/v1/dashboard/most-sold-products",
     *       summary="Get most sold products",
     *       description="Endpoint to get most sold products",
     *       tags={"Dashboard"},
     *       security={
     *           {"token": {}}
     *       },
     *
     *       @OA\Parameter(
     *           name="start_date",
     *           in="query",
     *           description="Start Date",
     *           required=true
     *       ),
     *       @OA\Parameter(
     *           name="end_date",
     *           in="query",
     *           description="End Date",
     *           required=true
     *       ),
     *       @OA\Parameter(
     *           name="limit",
     *           in="query",
     *           description="Limit (Default 5)",
     *       ),
     *
     *       @OA\Response(
     *          response=200,
     *          description="Get most sold products successfully",
     *
     *          @OA\JsonContent(
     *
     *              @OA\Property(property="status", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Get most sold products successfully"),
     *              @OA\Property(property="data", type="object", example={}),
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
     *              @OA\Property(property="message", type="string", example="Internal server error"),
     *          )
     *      ),
     * )
     */
    public function mostSoldProducts(Request $request)
    {
        // Set default range date to 30 days and limit
        $request->merge([
            'start_date' => $request->start_date ?? now()->subDays(30)->format('Y-m-d'),
            'end_date' => $request->end_date ?? now()->format('Y-m-d'),
            'limit' => $request->limit ?? 5,
        ]);

        $data = ProductService::mostSold($request, $request->limit);

        return $this->responseJson(
            $data['status'] ? 'success' : 'error',
            $data['message'],
            $data['data'],
            $data['statusCode']
        );
    }

    /**
     * @OA\Get(
     *       path="/api/v1/dashboard/most-sold-categories",
     *       summary="Get most sold categories",
     *       description="Endpoint to get most sold categories",
     *       tags={"Dashboard"},
     *       security={
     *           {"token": {}}
     *       },
     *
     *       @OA\Parameter(
     *           name="start_date",
     *           in="query",
     *           description="Start Date",
     *           required=true
     *       ),
     *       @OA\Parameter(
     *           name="end_date",
     *           in="query",
     *           description="End Date",
     *           required=true
     *       ),
     *
     *       @OA\Response(
     *          response=200,
     *          description="Get most sold categories successfully",
     *
     *          @OA\JsonContent(
     *
     *              @OA\Property(property="status", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Get most sold categories successfully"),
     *              @OA\Property(property="data", type="object", example={}),
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
     *              @OA\Property(property="message", type="string", example="Internal server error"),
     *          )
     *      ),
     * )
     */
    public function mostSoldCategories(Request $request)
    {
        // Set default range date to 30 days and limit
        $request->merge([
            'start_date' => $request->start_date ?? now()->subDays(30)->format('Y-m-d'),
            'end_date' => $request->end_date ?? now()->format('Y-m-d'),
        ]);

        $data = CategoryService::mostSold($request);

        return $this->responseJson(
            $data['status'] ? 'success' : 'error',
            $data['message'],
            $data['data'],
            $data['statusCode']
        );
    }
}
