<?php

namespace Tests\Feature\Api\V1;

use App\Models\Expense;
use App\Models\Inventory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    public function test_store_inventory_success()
    {
        $this->refreshDatabase();
        $this->seed();

        $invData = [
            'code' => $this->faker->unique()->word,
            'name' => $this->faker->name,
            'description' => $this->faker->sentence,
            'price' => $this->faker->randomFloat(2, 1, 100),
            'quantity' => $this->faker->randomNumber(2),
            'image' => UploadedFile::fake()->image('image.jpg'),
        ];
        $response = $this->post('/api/v1/inventories', $invData, $this->getFormAuthorizationHeader());

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'id',
                    'code',
                    'name',
                    'description',
                    'price',
                    'quantity',
                    'image',
                ],
            ]);

        $this->assertDatabaseHas('inventories', ['code' => $response['data']['code']]);

        $expense = Expense::where([
            'year' => date('Y'),
            'month' => date('m'),
        ])->first();
        $this->assertNotNull($expense);
        $this->assertEquals($response['data']['total'], $expense->amount);
    }

    public function test_get_inventories_success()
    {
        $this->refreshDatabase();
        $this->seed();

        $response = $this->get('/api/v1/inventories', $this->getAuthorizationHeader());

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    '*' => [
                        'id',
                        'code',
                        'name',
                        'description',
                        'price',
                        'quantity',
                        'image',
                    ],
                ],
            ]);
    }

    public function test_get_detail_inventory_success()
    {
        $this->refreshDatabase();
        $this->seed();

        $inventory = Inventory::factory()->create();

        $response = $this->get('/api/v1/inventories/'.$inventory->id, $this->getAuthorizationHeader());

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'id',
                    'code',
                    'name',
                    'description',
                    'price',
                    'quantity',
                    'image',
                ],
            ]);
    }

    public function test_update_inventory_success()
    {
        $this->refreshDatabase();
        $this->seed();

        $expense = Expense::where([
            'year' => date('Y'),
            'month' => date('m'),
        ])->first();
        $this->assertNull($expense);

        // Create inventory
        $invData = [
            'code' => $this->faker->unique()->word,
            'name' => $this->faker->name,
            'description' => $this->faker->sentence,
            'price' => $this->faker->randomFloat(2, 1, 100),
            'quantity' => $this->faker->randomNumber(2),
            'image' => UploadedFile::fake()->image('image.jpg'),
        ];
        $response = $this->post('/api/v1/inventories', $invData, $this->getFormAuthorizationHeader());

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'id',
                    'code',
                    'name',
                    'description',
                    'price',
                    'quantity',
                    'image',
                ],
            ]);

        $this->assertDatabaseHas('inventories', ['code' => $response['data']['code']]);
        $expense = Expense::where([
            'year' => date('Y'),
            'month' => date('m'),
        ])->first();
        $this->assertNotNull($expense);
        $this->assertEquals($response['data']['total'], $expense->amount);

        sleep(1); // Sleep for 1 second to make sure the code is different

        // Update inventory
        $inventory = Inventory::find($response['data']['id']);
        $updateData = [
            'code' => $this->faker->unique()->word,
            'name' => $this->faker->name,
            'description' => $this->faker->sentence,
            'price' => $inventory->price + 2,
            'quantity' => $inventory->quantity + 2,
            'image' => UploadedFile::fake()->image('image.jpg'),
        ];
        $response = $this->post('/api/v1/inventories/'.$inventory->id, $updateData, $this->getFormAuthorizationHeader());

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'id',
                    'code',
                    'name',
                    'description',
                    'price',
                    'quantity',
                    'image',
                ],
            ]);

        $this->assertDatabaseHas('inventories', ['code' => $response['data']['code']]);

        // TODO: Check expense balance
    }

    public function test_delete_inventory_success()
    {
        $this->refreshDatabase();
        $this->seed();

        $expense = Expense::where([
            'year' => date('Y'),
            'month' => date('m'),
        ])->first();
        $this->assertNull($expense);

        // Create inventory
        $invData = [
            'code' => $this->faker->unique()->word,
            'name' => $this->faker->name,
            'description' => $this->faker->sentence,
            'price' => $this->faker->randomFloat(2, 1, 100),
            'quantity' => $this->faker->randomNumber(2),
            'image' => UploadedFile::fake()->image('image.jpg'),
        ];
        $response = $this->post('/api/v1/inventories', $invData, $this->getFormAuthorizationHeader());

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'id',
                    'code',
                    'name',
                    'description',
                    'price',
                    'quantity',
                    'image',
                ],
            ]);

        $this->assertDatabaseHas('inventories', ['code' => $response['data']['code']]);
        $expense = Expense::where([
            'year' => date('Y'),
            'month' => date('m'),
        ])->first();
        $this->assertNotNull($expense);
        $this->assertEquals($response['data']['total'], $expense->amount);

        sleep(1); // Sleep for 1 second to make sure the code is different

        // Delete inventory
        $inventory = Inventory::find($response['data']['id']);
        $response = $this->delete('/api/v1/inventories/'.$inventory->id, [], $this->getAuthorizationHeader());

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
            ]);

        $this->assertSoftDeleted('inventories', ['id' => $inventory->id]);

        // TODO: Check expense balance
    }
}
