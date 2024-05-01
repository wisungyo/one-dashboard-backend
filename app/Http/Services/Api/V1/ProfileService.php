<?php

namespace App\Http\Services\Api\V1;

use App\Http\Resources\Api\V1\UserResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProfileService extends BaseResponse
{
    public function update($data): array
    {
        DB::beginTransaction();
        try {
            $message = __('Update profile successfully');

            $user = auth()->user();

            if (! empty($data['password'])) {
                $data['password'] = bcrypt($data['password']);
            } else {
                unset($data['password']);
            }

            if (isset($data['avatar'])) {
                $user->avatar()->delete();
                $user->avatar()->create([
                    'type' => 'avatar',
                    'size' => $data['avatar']->getSize(),
                    'mime_type' => $data['avatar']->getMimeType(),
                    'file_name' => $data['avatar']->getClientOriginalName(),
                    'path' => $data['avatar']->store('images/avatars'),
                    'height' => 0,
                    'width' => 0,
                ]);
            }
            $user->update($data);

            $resource = new UserResource($user);

            DB::commit();

        } catch (\Throwable $th) {
            DB::rollback();
            Log::error($th);

            return $this->responseError(__('Update profile failed'), 500, $th->getMessage());
        }

        return $this->responseSuccess($message, 200, $resource);
    }
}
