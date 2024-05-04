<?php

namespace App\Http\Controllers\Api\V1;

use App\Cores\ApiResponse;
use Facades\App\Http\Services\Api\V1\IncomeService;
use Illuminate\Http\Request;

class IncomeController extends Controller
{
    use ApiResponse;

    /**
     * @OA\Get(
     *       path="/api/v1/incomes",
     *       summary="Get list incomes ",
     *       description="Endpoint to get list incomes ",
     *       tags={"Income"},
     *       security={
     *           {"token": {}}
     *       },
     *
     *       @OA\Parameter(
     *           name="date",
     *           in="query",
     *           description="Date"
     *       ),
     *       @OA\Parameter(
     *           name="amount",
     *           in="query",
     *           description="Amount"
     *       ),
     *       @OA\Parameter(
     *           name="start_date",
     *           in="query",
     *           description="Start Date"
     *       ),
     *       @OA\Parameter(
     *           name="end_date",
     *           in="query",
     *           description="End Date"
     *       ),
     *       @OA\Parameter(
     *           name="sort",
     *           in="query",
     *           description="1 for Ascending -1 for Descending"
     *       ),
     *       @OA\Parameter(
     *           name="sort_by",
     *           in="query",
     *           description="Field to sort"
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
     *          description="Get list incomes successfully",
     *
     *          @OA\JsonContent(
     *
     *              @OA\Property(property="data", type="object", example={}),
     *              @OA\Property(property="pagination", type="object", example={}),
     *          )
     *      ),
     *
     *      @OA\Response(
     *          response=400,
     *          description="Get list incomes failed",
     *
     *          @OA\JsonContent(
     *
     *              @OA\Property(property="status", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Get list incomes failed"),
     *          )
     *      ),
     * )
     */
    public function index(Request $request)
    {
        $data = IncomeService::list($request);
        if (isset($data['status']) && ! $data['status']) {
            return $this->responseJson('error', $data['message'], $data['data'], $data['statusCode']);
        }

        return $this->responseJson(
            'pagination',
            __('Get list incomes successfully'),
            $data,
            $data['statusCode'],
            [$request->sort_by, $request->sort]
        );
    }

    /**
     * @OA\Get(
     *       path="/api/v1/incomes/{id}",
     *       summary="Get detail income",
     *       description="Endpoint to get detail income",
     *       tags={"Income"},
     *       security={
     *           {"token": {}}
     *       },
     *
     *       @OA\Parameter(
     *           name="id",
     *           in="path",
     *           description="ID",
     *           required=true,
     *       ),
     *
     *       @OA\Response(
     *          response=200,
     *          description="Get income successfully",
     *
     *          @OA\JsonContent(
     *
     *              @OA\Property(property="status", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Get income successfully"),
     *              @OA\Property(property="data", type="object", example={}),
     *          )
     *      ),
     *
     *      @OA\Response(
     *          response=404,
     *          description="Income not found",
     *
     *          @OA\JsonContent(
     *
     *              @OA\Property(property="status", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Income not found"),
     *          )
     *      ),
     * )
     */
    public function show($id)
    {
        $data = IncomeService::getById($id);

        return $this->responseJson(
            $data['status'] ? 'success' : 'error',
            $data['message'],
            $data['data'],
            $data['statusCode'],
        );
    }
}
