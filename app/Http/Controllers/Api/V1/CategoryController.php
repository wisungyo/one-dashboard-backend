<?php

namespace App\Http\Controllers\Api\V1;

use App\Cores\ApiResponse;
use App\Http\Requests\Api\V1\Category\StoreRequest;
use App\Http\Requests\Api\V1\Category\UpdateRequest;
use Facades\App\Http\Services\Api\V1\CategoryService;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    use ApiResponse;

    /**
     * @OA\Get(
     *       path="/api/v1/categories",
     *       summary="Get list categories ",
     *       description="Endpoint to get list categories ",
     *       tags={"Category"},
     *       security={
     *           {"token": {}}
     *       },
     *
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
     *          description="Get list categories successfully",
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
     *          description="Get list categories failed",
     *
     *          @OA\JsonContent(
     *
     *              @OA\Property(property="status", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Get list categories failed"),
     *          )
     *      ),
     * )
     */
    public function index(Request $request)
    {
        $data = CategoryService::list($request);
        if (isset($data['status']) && ! $data['status']) {
            return $this->responseJson('error', $data['message'], $data['data'], $data['statusCode']);
        }

        return $this->responseJson(
            'pagination',
            __('Get list categories successfully'),
            $data,
            $data['statusCode'],
            [$request->sort_by, $request->sort]
        );
    }

    /**
     * @OA\Post(
     *      path="/api/v1/categories",
     *      summary="Create a new category",
     *      description="Create a new category",
     *      tags={"Category"},
     *      security={
     *          {"token": {}}
     *      },
     *
     *      @OA\RequestBody(
     *          required=true,
     *          description="Data that needed to create a new category",
     *
     *          @OA\JsonContent(
     *              required={"name"},
     *
     *              @OA\Property(property="name", type="string", example="Elektronik"),
     *              @OA\Property(property="description", type="string", example="Elektronik"),
     *          ),
     *      ),
     *
     *      @OA\Response(
     *          response=201,
     *          description="Create a new category successfully",
     *
     *          @OA\JsonContent(
     *
     *              @OA\Property(property="status", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Create a new category successfully"),
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
     *          description="Create a new category failed",
     *      ),
     * )
     */
    public function store(StoreRequest $request)
    {
        $data = $request->validated();
        $data = CategoryService::store($data);

        return $this->responseJson(
            $data['status'] ? 'success' : 'error',
            $data['message'],
            $data['data'],
            $data['statusCode']
        );
    }

    /**
     * @OA\Get(
     *       path="/api/v1/categories/{id}",
     *       summary="Get detail category",
     *       description="Endpoint to get detail category",
     *       tags={"Category"},
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
     *          description="Get category successfully",
     *
     *          @OA\JsonContent(
     *
     *              @OA\Property(property="status", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Get category successfully"),
     *              @OA\Property(property="data", type="object", example={}),
     *          )
     *      ),
     *
     *      @OA\Response(
     *          response=404,
     *          description="Category not found",
     *
     *          @OA\JsonContent(
     *
     *              @OA\Property(property="status", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Category not found"),
     *          )
     *      ),
     * )
     */
    public function show($id)
    {
        $data = CategoryService::getById($id);

        return $this->responseJson(
            $data['status'] ? 'success' : 'error',
            $data['message'],
            $data['data'],
            $data['statusCode'],
        );
    }

    // NOTE : only can POST method for form data
    /**
     * @OA\Put(
     *       path="/api/v1/categories/{id}",
     *       summary="Update category",
     *       description="Endpoint to update category",
     *       tags={"Category"},
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
     *      @OA\RequestBody(
     *          required=true,
     *          description="Data that needed to update category",
     *
     *          @OA\JsonContent(
     *              required={"name"},
     *
     *              @OA\Property(property="name", type="string", example="Elektronik"),
     *              @OA\Property(property="description", type="string", example="Elektronik"),
     *          ),
     *      ),
     *
     *       @OA\Response(
     *          response=200,
     *          description="Update category successfully",
     *
     *          @OA\JsonContent(
     *
     *              @OA\Property(property="status", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Update category successfully"),
     *              @OA\Property(property="data", type="object", example={}),
     *          )
     *      ),
     *
     *      @OA\Response(
     *          response=400,
     *          description="Update category failed",
     *
     *          @OA\JsonContent(
     *
     *              @OA\Property(property="status", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Update category failed"),
     *          )
     *      ),
     * )
     */
    public function update($id, UpdateRequest $request)
    {
        $data = $request->validated();
        $data = CategoryService::update($id, $data);

        return $this->responseJson(
            $data['status'] ? 'success' : 'error',
            $data['message'],
            $data['data'],
            $data['statusCode']
        );
    }

    /**
     * @OA\Delete(
     *       path="/api/v1/categories/{id}",
     *       summary="Delete category",
     *       description="Endpoint to delete category",
     *       tags={"Category"},
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
     *          description="Delete category successfully",
     *
     *          @OA\JsonContent(
     *
     *              @OA\Property(property="status", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Delete category successfully"),
     *              @OA\Property(property="data", type="object", example={}),
     *          )
     *      ),
     *
     *      @OA\Response(
     *          response=404,
     *          description="Category not found",
     *
     *          @OA\JsonContent(
     *
     *              @OA\Property(property="status", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Category not found"),
     *          )
     *      ),
     * )
     */
    public function destroy($id)
    {
        $data = CategoryService::delete($id);

        return $this->responseJson(
            $data['status'] ? 'success' : 'error',
            $data['message'],
            $data['data'],
            $data['statusCode']
        );
    }
}
