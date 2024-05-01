<?php

namespace Tests\Feature\Api\V1;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    public function test_login_success()
    {
        $this->refreshDatabase();
        $this->seed();

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $this->getSuperAdminEmail(),
            'password' => $this->getSuperAdminPassword(),
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'token',
                ],
            ]);
    }

    public function test_login_invalid()
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'status' => false,
            ]);
    }

    public function test_logout_success()
    {
        $this->refreshDatabase();
        $this->seed();

        // FIXME: using actingAs
        $response = $this->post('/api/v1/auth/logout', [], $this->getAuthorizationHeader());

        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
            ]);
    }

    public function test_logout_unauthenticated()
    {
        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }
}
