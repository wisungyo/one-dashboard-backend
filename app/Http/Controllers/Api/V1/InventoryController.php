<?php

namespace App\Http\Controllers\Api\V1;

use App\Cores\ApiResponse;
use App\Http\Requests\Api\V1\Inventory\StoreRequest;
use App\Http\Requests\Api\V1\Inventory\UpdateRequest;
use Facades\App\Http\Services\Api\V1\InventoryService;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    use ApiResponse;

    /**
     * @OA\Get(
     *       path="/api/v1/inventories",
     *       summary="Get list inventories ",
     *       description="Endpoint to get list inventories ",
     *       tags={"Inventory"},
     *       security={
     *           {"token": {}}
     *       },
     *
     *       @OA\Parameter(
     *           name="code",
     *           in="query",
     *           description="Code"
     *       ),
     *       @OA\Parameter(
     *           name="name",
     *           in="query",
     *           description="name"
     *       ),
     *       @OA\Parameter(
     *           name="description",
     *           in="query",
     *           description="Description"
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
     *          description="Get list inventories successfully",
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
     *          description="Get list inventories failed",
     *
     *          @OA\JsonContent(
     *
     *              @OA\Property(property="status", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Get list inventories failed"),
     *          )
     *      ),
     * )
     */
    public function index(Request $request)
    {
        $data = InventoryService::list($request);
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
     *      path="/api/v1/inventories",
     *      summary="Create a new inventory",
     *      description="Create a new inventory",
     *      tags={"Inventory"},
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
     *                  required={"code", "name", "price", "quantity", "image"},
     *
     *                  @OA\Property(property="code", type="string", example="INV1X"),
     *                  @OA\Property(property="name", type="string", example="Choki-Choki"),
     *                  @OA\Property(property="description", type="string", example="Chocolate wafer"),
     *                  @OA\Property(property="price", type="number", example=10000),
     *                  @OA\Property(property="quantity", type="number", example=15),
     *                  @OA\Property(property="image", type="file"),
     *              )
     *          )
     *      ),
     *
     *      @OA\Response(
     *          response=201,
     *          description="Create a new inventory successfully",
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
     *          description="Create a new inventory failed",
     *      ),
     * )
     */
    public function store(StoreRequest $request)
    {
        $data = $request->validated();
        $data = InventoryService::store($data);

        return $this->responseJson(
            $data['status'] ? 'success' : 'error',
            $data['message'],
            $data['data'],
            $data['statusCode']
        );
    }

    /**
     * @OA\Get(
     *       path="/api/v1/inventories/{id}",
     *       summary="Get detail inventory",
     *       description="Endpoint to get detail inventory",
     *       tags={"Inventory"},
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
     *          description="Get inventory successfully",
     *
     *          @OA\JsonContent(
     *
     *              @OA\Property(property="status", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Get inventory successfully"),
     *              @OA\Property(property="data", type="object", example={}),
     *          )
     *      ),
     *
     *      @OA\Response(
     *          response=404,
     *          description="Inventory not found",
     *
     *          @OA\JsonContent(
     *
     *              @OA\Property(property="status", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Inventory not found"),
     *          )
     *      ),
     * )
     */
    public function show($id)
    {
        $data = InventoryService::getById($id);

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
     *       path="/api/v1/inventories/{id}",
     *       summary="Update inventory",
     *       description="Endpoint to update inventory",
     *       tags={"Inventory"},
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
     *                   required={"code", "name"},
     *
     *                   @OA\Property(property="code", type="string", example="INV1X"),
     *                   @OA\Property(property="name", type="string", example="Choki-Choki"),
     *                   @OA\Property(property="description", type="string", example="Chocolate wafer"),
     *                   @OA\Property(property="price", type="number", example=10000),
     *                   @OA\Property(property="quantity", type="number", example=15),
     *                   @OA\Property(property="image", type="file"),
     *               )
     *           )
     *       ),
     *
     *       @OA\Response(
     *          response=200,
     *          description="Update inventory successfully",
     *
     *          @OA\JsonContent(
     *
     *              @OA\Property(property="status", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Update inventory successfully"),
     *              @OA\Property(property="data", type="object", example={}),
     *          )
     *      ),
     *
     *      @OA\Response(
     *          response=400,
     *          description="Update inventory failed",
     *
     *          @OA\JsonContent(
     *
     *              @OA\Property(property="status", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Update inventory failed"),
     *          )
     *      ),
     * )
     */
    public function update($id, UpdateRequest $request)
    {
        $data = $request->validated();
        $data = InventoryService::update($id, $data);

        return $this->responseJson(
            $data['status'] ? 'success' : 'error',
            $data['message'],
            $data['data'],
            $data['statusCode']
        );
    }

    /**
     * @OA\Delete(
     *       path="/api/v1/inventories/{id}",
     *       summary="Delete inventory",
     *       description="Endpoint to delete inventory",
     *       tags={"Inventory"},
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
     *          description="Delete inventory successfully",
     *
     *          @OA\JsonContent(
     *
     *              @OA\Property(property="status", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Delete inventory successfully"),
     *              @OA\Property(property="data", type="object", example={}),
     *          )
     *      ),
     *
     *      @OA\Response(
     *          response=404,
     *          description="Inventory not found",
     *
     *          @OA\JsonContent(
     *
     *              @OA\Property(property="status", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Inventory not found"),
     *          )
     *      ),
     * )
     */
    public function destroy($id)
    {
        $data = InventoryService::delete($id);

        return $this->responseJson(
            $data['status'] ? 'success' : 'error',
            $data['message'],
            $data['data'],
            $data['statusCode']
        );
    }
}
