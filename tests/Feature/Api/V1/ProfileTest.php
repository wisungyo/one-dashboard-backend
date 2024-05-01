<?php

namespace Tests\Feature\Api\V1;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    public function test_get_profile_success()
    {
        $this->refreshDatabase();
        $this->seed();

        Sanctum::actingAs(
            $this->getSuperAdminUser(),
            ['*']
        );

        $response = $this->get('/api/v1/profile');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'email',
                    'avatar',
                ],
            ]);
    }

    public function test_update_profile_success()
    {
        $this->refreshDatabase();
        $this->seed();

        Sanctum::actingAs(
            $this->getSuperAdminUser(),
            ['*']
        );

        $updateData = [
            'name' => 'Super Admin update',
            'email' => 'superupdate@onedashboard.com',
        ];

        $response = $this->postJson('/api/v1/profile', $updateData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'name',
                    'email',
                    'avatar',
                ],
            ]);

        $this->assertDatabaseHas('users', ['email' => $updateData['email'], 'name' => $updateData['name']]);
    }
}
