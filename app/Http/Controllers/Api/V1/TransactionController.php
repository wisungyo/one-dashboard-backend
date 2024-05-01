<?php

namespace App\Http\Controllers\Api\V1;

use App\Cores\ApiResponse;
use App\Enums\TransactionType;
use App\Http\Requests\Api\V1\Transaction\StoreRequest;
use App\Http\Requests\Api\V1\Transaction\UpdateRequest;
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
     *           name="inventory_id",
     *           in="query",
     *           description="Inventory ID"
     *       ),
     *       @OA\Parameter(
     *           name="code",
     *           in="query",
     *           description="Code"
     *       ),
     *       @OA\Parameter(
     *           name="type",
     *           in="query",
     *           description="Type ('in' or 'out')"
     *       ),
     *       @OA\Parameter(
     *           name="price",
     *           in="query",
     *           description="Price"
     *       ),
     *       @OA\Parameter(
     *           name="quantity",
     *           in="query",
     *           description="Quantity"
     *       ),
     *       @OA\Parameter(
     *           name="total",
     *           in="query",
     *           description="Total"
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
     *          @OA\MediaType(
     *              mediaType="multipart/form-data",
     *
     *              @OA\Schema(
     *                  required={"inventory_id", "quantity"},
     *
     *                  @OA\Property(property="inventory_id", type="number", example=1),
     *                  @OA\Property(property="quantity", type="number", example=2),
     *                  @OA\Property(property="image", type="file"),
     *              )
     *          )
     *      ),
     *
     *      @OA\Response(
     *          response=201,
     *          description="Create a new transaction successfully",
     *
     *          @OA\JsonContent(
     *
     *              @OA\Property(property="status", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Register customer successfully"),
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

    // NOTE : only can POST method for form data
    /**
     * @OA\Post(
     *       path="/api/v1/transactions/{id}",
     *       summary="Update transaction",
     *       description="Endpoint to update transaction",
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
     *       @OA\RequestBody(
     *
     *           @OA\MediaType(
     *               mediaType="multipart/form-data",
     *
     *               @OA\Schema(
     *                   required={"quantity"},
     *
     *                   @OA\Property(property="quantity", type="number", example=15),
     *                   @OA\Property(property="image", type="file"),
     *               )
     *           )
     *       ),
     *
     *       @OA\Response(
     *          response=200,
     *          description="Update transaction successfully",
     *
     *          @OA\JsonContent(
     *
     *              @OA\Property(property="status", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Update transaction successfully"),
     *              @OA\Property(property="data", type="object", example={}),
     *          )
     *      ),
     *
     *      @OA\Response(
     *          response=400,
     *          description="Update transaction failed",
     *
     *          @OA\JsonContent(
     *
     *              @OA\Property(property="status", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Update transaction failed"),
     *          )
     *      ),
     * )
     */
    public function update($id, UpdateRequest $request)
    {
        $data = $request->validated();
        $data = TransactionService::update($id, $data);

        return $this->responseJson(
            $data['status'] ? 'success' : 'error',
            $data['message'],
            $data['data'],
            $data['statusCode']
        );
    }

    /**
     * @OA\Delete(
     *       path="/api/v1/transactions/{id}",
     *       summary="Delete transaction",
     *       description="Endpoint to delete transaction",
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
     *          description="Delete transaction successfully",
     *
     *          @OA\JsonContent(
     *
     *              @OA\Property(property="status", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Delete transaction successfully"),
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
    public function destroy($id)
    {
        $data = TransactionService::delete($id);

        return $this->responseJson(
            $data['status'] ? 'success' : 'error',
            $data['message'],
            $data['data'],
            $data['statusCode']
        );
    }
}
