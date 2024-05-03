<?php

namespace App\Http\Controllers\Api\V1;

use App\Cores\ApiResponse;
use App\Http\Requests\Api\V1\ProfileRequest;
use App\Http\Resources\Api\V1\UserResource;
use Facades\App\Http\Services\Api\V1\ProfileService;

class ProfileController extends Controller
{
    use ApiResponse;

    /**
     * @OA\Get(
     *       path="/api/v1/profile",
     *       summary="Get current user's profile",
     *       description="Endpoint to get logged in user",
     *       tags={"Profile"},
     *       security={
     *           {"token": {}}
     *       },
     *
     *       @OA\Response(
     *          response=200,
     *          description="Get profile successfully",
     *
     *          @OA\JsonContent(
     *
     *              @OA\Property(property="status", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Get profile successfully"),
     *              @OA\Property(property="data", type="object", example={}),
     *          )
     *      ),
     *
     *      @OA\Response(
     *          response=404,
     *          description="User Not Found",
     *
     *          @OA\JsonContent(
     *
     *              @OA\Property(property="status", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="User Not Found"),
     *          )
     *      ),
     * )
     */
    public function index()
    {
        $user = auth()->user();

        return $this->responseJson(
            $user ? 'success' : 'error',
            $user ? __('Get profile successfully') : __('User not found'),
            $user ? new UserResource($user) : '',
            $user ? 200 : 404
        );
    }

    // NOTE : only can POST method for form data
    /**
     * @OA\Post(
     *       path="/api/v1/profile",
     *       summary="Update current user's profile",
     *       description="Endpoint to update logged in user",
     *       tags={"Profile"},
     *       security={
     *           {"token": {}}
     *       },
     *
     *       @OA\RequestBody(
     *
     *           @OA\MediaType(
     *               mediaType="multipart/form-data",
     *
     *               @OA\Schema(
     *                   required={"name", "email"},
     *
     *                   @OA\Property(property="name", type="string", description="Name", example="my name is supeeer"),
     *                   @OA\Property(property="email", type="email", description="Email", example="super@onedashboard.com"),
     *                   @OA\Property(property="phone_number", type="string", description="Phone Number", example="+62818181xxxxx"),
     *                   @OA\Property(property="bio", type="string", description="Bio", example="Lorem ipsum"),
     *                   @OA\Property(property="password", type="string", description="Password (for update password)", format="password", example=null),
     *                   @OA\Property(property="avatar", type="file", description="Avatar"),
     *               )
     *           )
     *       ),
     *
     *       @OA\Response(
     *           response=200,
     *           description="Update profile successfully",
     *
     *           @OA\JsonContent(
     *
     *               @OA\Property(property="status", type="boolean", example=true),
     *               @OA\Property(property="message", type="string", example="Update profile successfully"),
     *               @OA\Property(property="data", type="object", example={}),
     *           )
     *       ),
     *
     *       @OA\Response(
     *           response=400,
     *           description="Update profile failed",
     *
     *           @OA\JsonContent(
     *
     *               @OA\Property(property="status", type="boolean", example=false),
     *               @OA\Property(property="message", type="string", example="Update profile failed"),
     *           )
     *       ),
     *
     *       @OA\Response(
     *           response=422,
     *           description="Wrong credentials response",
     *
     *           @OA\JsonContent(
     *
     *               @OA\Property(property="message", type="string", example="The given data was invalid."),
     *               @OA\Property(property="errors", type="object", example={}),
     *           )
     *       )
     * )
     */
    public function update(ProfileRequest $request)
    {
        $data = $request->validated();
        $data = ProfileService::update($data);

        return $this->responseJson(
            $data['status'] ? 'success' : 'error',
            $data['message'],
            $data['data'],
            $data['statusCode']
        );
    }
}
