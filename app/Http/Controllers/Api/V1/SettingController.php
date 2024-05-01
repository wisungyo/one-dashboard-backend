<?php

namespace App\Http\Controllers\Api\V1;

use App\Cores\ApiResponse;
use App\Http\Requests\Api\V1\Setting\UpdateRequest;
use Facades\App\Http\Services\Api\V1\SettingService;

class SettingController extends Controller
{
    use ApiResponse;

    const LOGO_KEY_SETTING = 'logo';

    /**
     * @OA\Get(
     *      path="/api/v1/settings/logo",
     *      summary="Get logo image",
     *      description="Get logo image",
     *      tags={"Settings"},
     *      security={
     *          {"token": {}}
     *      },
     *
     *      @OA\Response(
     *          response=200,
     *          description="Get setting successfully",
     *
     *          @OA\JsonContent(
     *
     *              @OA\Property(property="status", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Get setting successfully"),
     *              @OA\Property(property="data", type="object", example={}),
     *          )
     *      ),
     *
     *      @OA\Response(
     *          response=404,
     *          description="Get setting failed",
     *      ),
     * )
     */
    public function logo()
    {
        $data = SettingService::get(self::LOGO_KEY_SETTING);

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
     *      path="/api/v1/settings/logo",
     *      summary="Update logo",
     *      description="Update logo",
     *      tags={"Settings"},
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
     *                  required={"image"},
     *
     *                  @OA\Property(property="image", type="file"),
     *              )
     *          )
     *      ),
     *
     *      @OA\Response(
     *          response=200,
     *          description="Update setting successfully",
     *
     *          @OA\JsonContent(
     *
     *              @OA\Property(property="status", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Update setting successfully"),
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
     *          response=400,
     *          description="Update setting failed",
     *      ),
     * )
     */
    public function updateLogo(UpdateRequest $request)
    {
        $data = $request->validated();
        $data['name'] = self::LOGO_KEY_SETTING;
        $data['key'] = self::LOGO_KEY_SETTING;
        $data['value'] = 'image';
        $data = SettingService::updateByKey(self::LOGO_KEY_SETTING, $data);

        return $this->responseJson(
            $data['status'] ? 'success' : 'error',
            $data['message'],
            $data['data'],
            $data['statusCode'],
        );
    }
}
