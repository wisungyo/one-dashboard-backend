<?php

namespace App\Http\Services\Api\V1;

use App\Http\Resources\Api\V1\LoginResource;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class UserService extends BaseResponse
{
    /**
     * Authentication for login
     *
     * @param  array  $data
     */
    public function auth($data)
    {
        $credentials = [
            'email' => ($data['email'] ?? null),
            'password' => $data['password'],
        ];

        $conditions = $credentials;
        unset($conditions['password']);
        $user = User::where($conditions)->first();

        if (! Auth::attempt($credentials)) {
            return $this->responseError('Username or password incorrect.', 400);
        }

        $messages = __('Login successfully.');

        $data = new LoginResource($user);

        return $this->responseSuccess($messages, 200, $data);
    }
}
