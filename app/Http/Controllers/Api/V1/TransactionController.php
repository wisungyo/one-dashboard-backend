<?php

namespace App\Http\Controllers\Api\V1;

use App\Cores\ApiResponse;
use App\Enums\TransactionType;
use App\Http\Requests\Api\V1\Transaction\StoreRequest;
use Facades\App\Http\Services\Api\V1\TransactionService;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    use ApiResponse;

    /**
     * @OA\Get(
     *       path="/api/v1/transactions",
     *       summary="Get list transactions ",
     *       description="Endpoint to get list transactions ",
     *       tags={"Transaction"},
     *       security={
     *           {"token": {}}
     *       },
     *
     *       @OA\Parameter(
     *           name="category_id",
     *           in="query",
     *           description="Category ID"
     *       ),
     *       @OA\Parameter(
     *           name="product_id",
     *           in="query",
     *           description="Product ID"
     *       ),
     *       @OA\Parameter(
     *           name="code",
     *           in="query",
     *           description="Code"
     *       ),
     *       @OA\Parameter(
     *           name="type",
     *           in="query",
     *           description="Type ('IN' or 'OUT')"
     *       ),
     *       @OA\Parameter(
     *           name="total_item",
     *           in="query",
     *           description="Total Item"
     *       ),
     *       @OA\Parameter(
     *           name="total_quantity",
     *           in="query",
     *           description="Total Quantity"
     *       ),
     *       @OA\Parameter(
     *           name="total_price",
     *           in="query",
     *           description="Total Price"
     *       ),
     *       @OA\Parameter(
     *           name="customer_name",
     *           in="query",
     *           description="Customer Name"
     *       ),
     *       @OA\Parameter(
     *           name="customer_phone",
     *           in="query",
     *           description="Customer Phone"
     *       ),
     *       @OA\Parameter(
     *           name="customer_address",
     *           in="query",
     *           description="Customer Address"
     *       ),
     *       @OA\Parameter(
     *           name="note",
     *           in="query",
     *           description="Note"
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
     *          description="Get list transactions successfully",
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
     *          description="Get list transactions failed",
     *
     *          @OA\JsonContent(
     *
     *              @OA\Property(property="status", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Get list transactions failed"),
     *          )
     *      ),
     * )
     */
    public function index(Request $request)
    {
        $data = TransactionService::list($request);
        if (isset($data['status']) && ! $data['status']) {
            return $this->responseJson('error', $data['message'], $data['data'], $data['statusCode']);
        }

        return $this->responseJson(
            'pagination',
            __('Get list transactions successfully'),
            $data,
            $data['statusCode'],
            [$request->sort_by, $request->sort]
        );
    }

    /**
     * @OA\Post(
     *      path="/api/v1/transactions",
     *      summary="Create a new transaction",
     *      description="Create a new transaction",
     *      tags={"Transaction"},
     *      security={
     *          {"token": {}}
     *      },
     *
     *      @OA\RequestBody(
     *
     *          required=true,
     *          description="Data that needed to create a new transaction",
     *
     *          @OA\JsonContent(
     *              required={"note", "items"},
     *
     *              @OA\Property(property="customer_name", type="string", example="John Doe"),
     *              @OA\Property(property="customer_phone", type="string", example="08123456789"),
     *              @OA\Property(property="customer_address", type="string", example="Jl. Raya No. 1"),
     *              @OA\Property(property="note", type="string", example="Buy some items"),
     *              @OA\Property(property="created_at", type="string", example="2024-04-04 15:30:30"),
     *              @OA\Property(
     *                  property="items",
     *                  type="array",
     *                  description="Array of transaction items",
     *
     *                   @OA\Items(
     *                      type="object",
     *                      required={"product_id", "quantity"},
     *
     *                      @OA\Property(property="product_id", type="integer", description="Product ID", example=1),
     *                      @OA\Property(property="quantity", type="integer", description="Quantity of the item", example=1),
     *                      @OA\Property(property="note", type="string", description="Note of the item", example="Buy some items"),
     *                  ),
     *              ),
     *          ),
     *      ),
     *
     *      @OA\Response(
     *          response=201,
     *          description="Create a new transaction successfully",
     *
     *          @OA\JsonContent(
     *
     *              @OA\Property(property="status", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Register a new transaction successfully"),
     *              @OA\Property(property="data", type="object", example={}),
     *          )
     *      ),
     *
     *      @OA\Response(
     *          response=422,
     *          description="Wrong credentials response",
     *
     *          @OA\JsonContent(
     *
     *              @OA\Property(property="message", type="string", example="The given data was invalid."),
     *              @OA\Property(property="errors", type="object", example={}),
     *          )
     *      ),
     *
     *      @OA\Response(
     *          response=500,
     *          description="Create a new transactions failed",
     *      ),
     * )
     */
    public function store(StoreRequest $request)
    {
        $data = $request->validated();
        $data['type'] = TransactionType::OUT;
        $data['created_at'] = $data['created_at'] ?? now();
        $data = TransactionService::store($data);

        return $this->responseJson(
            $data['status'] ? 'success' : 'error',
            $data['message'],
            $data['data'],
            $data['statusCode']
        );
    }

    /**
     * @OA\Get(
     *       path="/api/v1/transactions/{id}",
     *       summary="Get detail transaction",
     *       description="Endpoint to get detail transaction",
     *       tags={"Transaction"},
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
     *          description="Get transaction successfully",
     *
     *          @OA\JsonContent(
     *
     *              @OA\Property(property="status", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Get transaction successfully"),
     *              @OA\Property(property="data", type="object", example={}),
     *          )
     *      ),
     *
     *      @OA\Response(
     *          response=404,
     *          description="Transaction not found",
     *
     *          @OA\JsonContent(
     *
     *              @OA\Property(property="status", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Transaction not found"),
     *          )
     *      ),
     * )
     */
    public function show($id)
    {
        $data = TransactionService::getById($id);

        return $this->responseJson(
            $data['status'] ? 'success' : 'error',
            $data['message'],
            $data['data'],
            $data['statusCode'],
        );
    }
}
