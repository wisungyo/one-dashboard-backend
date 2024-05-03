<?php

namespace App\Http\Controllers\Api\V1;

use App\Cores\ApiResponse;
use App\Http\Requests\Api\V1\Product\StoreRequest;
use App\Http\Requests\Api\V1\Product\UpdateRequest;
use Facades\App\Http\Services\Api\V1\ProductService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    use ApiResponse;

    /**
     * @OA\Get(
     *       path="/api/v1/products",
     *       summary="Get list products ",
     *       description="Endpoint to get list products ",
     *       tags={"Product"},
     *       security={
     *           {"token": {}}
     *       },
     *
     *       @OA\Parameter(
     *           name="category_id",
     *           in="query",
     *           description="Categoy ID"
     *       ),
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
     *          description="Get list products successfully",
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
     *          description="Get list products failed",
     *
     *          @OA\JsonContent(
     *
     *              @OA\Property(property="status", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Get list products failed"),
     *          )
     *      ),
     * )
     */
    public function index(Request $request)
    {
        $data = ProductService::list($request);
        if (isset($data['status']) && ! $data['status']) {
            return $this->responseJson('error', $data['message'], $data['data'], $data['statusCode']);
        }

        return $this->responseJson(
            'pagination',
            __('Get list products successfully'),
            $data,
            $data['statusCode'],
            [$request->sort_by, $request->sort]
        );
    }

    /**
     * @OA\Post(
     *      path="/api/v1/products",
     *      summary="Create a new product",
     *      description="Create a new product",
     *      tags={"Product"},
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
     *                  required={"category_id", "code", "name", "price", "quantity", "image"},
     *
     *                  @OA\Property(property="category_id", type="number", example=1),
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
     *          description="Create a new product successfully",
     *
     *          @OA\JsonContent(
     *
     *              @OA\Property(property="status", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Create a new product successfully"),
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
     *          description="Create a new product failed",
     *      ),
     * )
     */
    public function store(StoreRequest $request)
    {
        $data = $request->validated();
        $data = ProductService::store($data);

        return $this->responseJson(
            $data['status'] ? 'success' : 'error',
            $data['message'],
            $data['data'],
            $data['statusCode']
        );
    }

    /**
     * @OA\Get(
     *       path="/api/v1/products/{id}",
     *       summary="Get detail product",
     *       description="Endpoint to get detail product",
     *       tags={"Product"},
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
     *          description="Get product successfully",
     *
     *          @OA\JsonContent(
     *
     *              @OA\Property(property="status", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Get product successfully"),
     *              @OA\Property(property="data", type="object", example={}),
     *          )
     *      ),
     *
     *      @OA\Response(
     *          response=404,
     *          description="Product not found",
     *
     *          @OA\JsonContent(
     *
     *              @OA\Property(property="status", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Product not found"),
     *          )
     *      ),
     * )
     */
    public function show($id)
    {
        $data = ProductService::getById($id);

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
     *       path="/api/v1/products/{id}",
     *       summary="Update product",
     *       description="Endpoint to update product",
     *       tags={"Product"},
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
     *                   required={"category_id", "code", "name", "price", "quantity"},
     *
     *                   @OA\Property(property="category_id", type="number", example=1),
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
     *          description="Update product successfully",
     *
     *          @OA\JsonContent(
     *
     *              @OA\Property(property="status", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Update product successfully"),
     *              @OA\Property(property="data", type="object", example={}),
     *          )
     *      ),
     *
     *      @OA\Response(
     *          response=400,
     *          description="Update product failed",
     *
     *          @OA\JsonContent(
     *
     *              @OA\Property(property="status", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Update product failed"),
     *          )
     *      ),
     * )
     */
    public function update($id, UpdateRequest $request)
    {
        $data = $request->validated();
        $data = ProductService::update($id, $data);

        return $this->responseJson(
            $data['status'] ? 'success' : 'error',
            $data['message'],
            $data['data'],
            $data['statusCode']
        );
    }

    /**
     * @OA\Delete(
     *       path="/api/v1/products/{id}",
     *       summary="Delete product",
     *       description="Endpoint to delete product",
     *       tags={"Product"},
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
     *          description="Delete product successfully",
     *
     *          @OA\JsonContent(
     *
     *              @OA\Property(property="status", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Delete product successfully"),
     *              @OA\Property(property="data", type="object", example={}),
     *          )
     *      ),
     *
     *      @OA\Response(
     *          response=404,
     *          description="Product not found",
     *
     *          @OA\JsonContent(
     *
     *              @OA\Property(property="status", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Product not found"),
     *          )
     *      ),
     * )
     */
    public function destroy($id)
    {
        $data = ProductService::delete($id);

        return $this->responseJson(
            $data['status'] ? 'success' : 'error',
            $data['message'],
            $data['data'],
            $data['statusCode']
        );
    }
}
