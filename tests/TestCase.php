<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function getSuperAdminEmail()
    {
        return 'super@onedashboard.com';
    }

    public function getSuperAdminPassword()
    {
        return 'test1234';
    }

    public function getSuperAdminUser()
    {
        return User::whereEmail($this->getSuperAdminEmail())->first();
    }

    public function getSuperAdminToken()
    {
        $user = $this->getSuperAdminUser();

        return $user->createToken('authToken')->plainTextToken;
    }

    public function getAuthorizationHeader()
    {
        return [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer '.$this->getSuperAdminToken(),
        ];
    }

    public function getFormAuthorizationHeader()
    {
        return [
            'Accept' => 'multipart/form-data',
            'Authorization' => 'Bearer '.$this->getSuperAdminToken(),
        ];
    }
}
