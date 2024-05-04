<?php

namespace App\Http\Controllers\Api\V1;

use App\Cores\ApiResponse;
use Facades\App\Http\Services\Api\V1\ExpenseService;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    use ApiResponse;

    /**
     * @OA\Get(
     *       path="/api/v1/expenses",
     *       summary="Get list expenses ",
     *       description="Endpoint to get list expenses ",
     *       tags={"Expense"},
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
     *          description="Get list expenses successfully",
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
     *          description="Get list expenses failed",
     *
     *          @OA\JsonContent(
     *
     *              @OA\Property(property="status", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Get list expenses failed"),
     *          )
     *      ),
     * )
     */
    public function index(Request $request)
    {
        $data = ExpenseService::list($request);
        if (isset($data['status']) && ! $data['status']) {
            return $this->responseJson('error', $data['message'], $data['data'], $data['statusCode']);
        }

        return $this->responseJson(
            'pagination',
            __('Get list expenses successfully'),
            $data,
            $data['statusCode'],
            [$request->sort_by, $request->sort]
        );
    }

    /**
     * @OA\Get(
     *       path="/api/v1/expenses/{id}",
     *       summary="Get detail expense",
     *       description="Endpoint to get detail expense",
     *       tags={"Expense"},
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
     *          description="Get expense successfully",
     *
     *          @OA\JsonContent(
     *
     *              @OA\Property(property="status", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Get expense successfully"),
     *              @OA\Property(property="data", type="object", example={}),
     *          )
     *      ),
     *
     *      @OA\Response(
     *          response=404,
     *          description="Expense not found",
     *
     *          @OA\JsonContent(
     *
     *              @OA\Property(property="status", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Expense not found"),
     *          )
     *      ),
     * )
     */
    public function show($id)
    {
        $data = ExpenseService::getById($id);

        return $this->responseJson(
            $data['status'] ? 'success' : 'error',
            $data['message'],
            $data['data'],
            $data['statusCode'],
        );
    }
}
