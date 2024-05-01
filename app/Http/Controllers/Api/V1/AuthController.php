<?php

namespace App\Http\Controllers\Api\V1;

use App\Cores\ApiResponse;
use App\Http\Requests\Api\V1\LoginRequest;
use Facades\App\Http\Services\Api\V1\UserService;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    use ApiResponse;

    /**
     * @OA\Post(
     *      path="/api/v1/auth/login",
     *      summary="Sign in",
     *      description="Login by email, password",
     *      tags={"Auth"},
     *
     *      @OA\RequestBody(
     *          required=true,
     *          description="Pass user credentials",
     *
     *          @OA\JsonContent(
     *              required={"email", "password"},
     *
     *              @OA\Property(property="email", type="email", example="super@onedashboard.com"),
     *              @OA\Property(property="password", type="string", format="password", example="test1234"),
     *          ),
     *      ),
     *
     *      @OA\Response(
     *          response=200,
     *          description="Login successfully",
     *
     *          @OA\JsonContent(
     *
     *              @OA\Property(property="status", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Login successfully"),
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
     *      )
     * )
     */
    public function login(LoginRequest $request)
    {
        $data = $request->validated();

        $login = UserService::auth($data);

        return $this->responseJson(
            $login['status'] ? 'success' : 'error',
            $login['message'],
            $login['data'],
            $login['statusCode']
        );
    }

    /**
     * @OA\Post(
     *       path="/api/v1/auth/logout",
     *       summary="Log user out ",
     *       description="Endpoint to log current user out",
     *       tags={"Auth"},
     *       security={
     *           {"token": {}}
     *       },
     *
     *       @OA\Response(
     *           response=200,
     *           description="Logout successfully",
     *
     *           @OA\JsonContent(
     *
     *              @OA\Property(property="status", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Logout successfully"),
     *           )
     *       ),
     *
     *       @OA\Response(
     *           response=400,
     *           description="Logout failed",
     *
     *           @OA\JsonContent(
     *
     *              @OA\Property(property="status", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Logout failed"),
     *           )
     *       ),
     *
     *       @OA\Response(
     *           response=401,
     *           description="Unauthorized",
     *
     *           @OA\JsonContent(
     *
     *              @OA\Property(property="status", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Unauthorized"),
     *           )
     *       ),
     * )
     */
    public function logout()
    {
        try {
            $user = auth()->user();
            if (! $user) {
                return $this->responseJson('error', 'Unauthorized.', '', 401);
            }

            $revoke = $user->currentAccessToken()->delete();
            /**Use below code if you want to log current user out in all devices */
            // $revoke = $user->tokens()->delete();

            if (! $revoke) {
                return $this->responseJson('error', 'Revoke failed', [], 400);
            }
        } catch (\Throwable $th) {
            //throw $th;
            Log::error($th);

            return $this->responseJson('error', 'Logout failed', [], 500);
        }

        return $this->responseJson('success', __('Logout successfully'), [], 200);
    }
}
