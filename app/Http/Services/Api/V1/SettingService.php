<?php

namespace App\Http\Services\Api\V1;

use App\Http\Resources\Api\V1\SettingResource;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;

class SettingService extends BaseResponse
{
    /**
     * Get Setting.
     *
     * @param  \App\Models\Setting::key  $key
     */
    public function get($key): array
    {
        try {
            $setting = setting($key);
            if (! $setting) {
                return $this->responseError(__('Setting not found'), 404);
            }
            $data = new SettingResource($setting);

            return $this->responseSuccess(__('Get setting successfully'), 200, $data);
        } catch (\Throwable $th) {
            Log::error($th);

            return $this->responseError(__('Get setting failed', 500, $th->getMessage()));
        }
    }

    /**
     * Update Setting By Key.
     *
     * @param  \App\Models\Setting::key  $key
     * @param  array  $data
     */
    public function updateByKey($key, $data): array
    {
        try {
            $setting = setting($key);
            if (! $setting) {
                $setting = Setting::create(array_merge(['key' => $key], $data));
            } else {
                $setting = updateSetting($setting->key, $data);
            }

            if (isset($data['image'])) {
                $setting->images()->delete();
                $setting->images()->create([
                    'type' => 'setting',
                    'size' => $data['image']->getSize(),
                    'mime_type' => $data['image']->getMimeType(),
                    'file_name' => $data['image']->getClientOriginalName(),
                    'path' => $data['image']->store('images/settings'),
                    'height' => 0,
                    'width' => 0,
                ]);
            }

            $data = new SettingResource($setting);

            return $this->responseSuccess(__('Update setting successfully'), 200, $data);
        } catch (\Throwable $th) {
            Log::error($th);

            return $this->responseError(__('Update setting failed', 500, $th->getMessage()));
        }
    }
}
