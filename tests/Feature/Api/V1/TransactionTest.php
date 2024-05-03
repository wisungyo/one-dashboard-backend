<?php

namespace Tests\Feature\Api\V1;

use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    public function test_store_transaction_success()
    {
        $this->refreshDatabase();
        $this->seed();

        // Create product
        $invData = [
            'code' => $this->faker->unique()->word,
            'name' => $this->faker->name,
            'description' => $this->faker->sentence,
            'price' => $this->faker->randomFloat(2, 1, 100),
            'quantity' => $this->faker->randomNumber(2),
            'image' => UploadedFile::fake()->image('image.jpg'),
        ];
        $response = $this->post('/api/v1/products', $invData, $this->getFormAuthorizationHeader());

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

        $this->assertDatabaseHas('products', ['code' => $response['data']['code']]);

        // Create transaction
        $transData = [
            'product_id' => $response['data']['id'],
            'quantity' => 1,
            'image' => UploadedFile::fake()->image('image.jpg'),
        ];
        $response = $this->post('/api/v1/transactions', $transData, $this->getFormAuthorizationHeader());

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'id',
                    'product_id',
                    'code',
                    'type',
                    'price',
                    'quantity',
                    'total',
                    'image',
                ],
            ]);
    }

    public function test_get_transactions_success()
    {
        $this->refreshDatabase();
        $this->seed();

        // Create product
        $invData = [
            'code' => $this->faker->unique()->word,
            'name' => $this->faker->name,
            'description' => $this->faker->sentence,
            'price' => $this->faker->randomFloat(2, 1, 100),
            'quantity' => $this->faker->randomNumber(2),
            'image' => UploadedFile::fake()->image('image.jpg'),
        ];
        $response = $this->post('/api/v1/products', $invData, $this->getFormAuthorizationHeader());

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

        $this->assertDatabaseHas('products', ['code' => $response['data']['code']]);

        // Create transaction
        $transData = [
            'product_id' => $response['data']['id'],
            'quantity' => 1,
            'image' => UploadedFile::fake()->image('image.jpg'),
        ];
        $response = $this->post('/api/v1/transactions', $transData, $this->getFormAuthorizationHeader());

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'id',
                    'product_id',
                    'code',
                    'type',
                    'price',
                    'quantity',
                    'total',
                    'image',
                ],
            ]);

        $response = $this->get('/api/v1/transactions', $this->getAuthorizationHeader());

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    '*' => [
                        'id',
                        'product_id',
                        'code',
                        'type',
                        'price',
                        'quantity',
                        'total',
                        'image',
                    ],
                ],
            ]);

        // TODO: Check income balance
    }

    public function test_get_detail_transaction_success()
    {
        $this->refreshDatabase();
        $this->seed();

        // Create product
        $invData = [
            'code' => $this->faker->unique()->word,
            'name' => $this->faker->name,
            'description' => $this->faker->sentence,
            'price' => $this->faker->randomFloat(2, 1, 100),
            'quantity' => $this->faker->randomNumber(2),
            'image' => UploadedFile::fake()->image('image.jpg'),
        ];
        $response = $this->post('/api/v1/products', $invData, $this->getFormAuthorizationHeader());

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

        $this->assertDatabaseHas('products', ['code' => $response['data']['code']]);

        // Create transaction
        $transData = [
            'product_id' => $response['data']['id'],
            'quantity' => 1,
            'image' => UploadedFile::fake()->image('image.jpg'),
        ];
        $response = $this->post('/api/v1/transactions', $transData, $this->getFormAuthorizationHeader());

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'id',
                    'product_id',
                    'code',
                    'type',
                    'price',
                    'quantity',
                    'total',
                    'image',
                ],
            ]);

        $transaction = Transaction::find($response['data']['id']);

        $response = $this->get('/api/v1/transactions/'.$transaction->id, $this->getAuthorizationHeader());

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'id',
                    'product_id',
                    'code',
                    'type',
                    'price',
                    'quantity',
                    'total',
                    'image',
                ],
            ]);
    }

    public function test_update_transaction_success()
    {
        $this->refreshDatabase();
        $this->seed();

        // Create product
        $invData = [
            'code' => $this->faker->unique()->word,
            'name' => $this->faker->name,
            'description' => $this->faker->sentence,
            'price' => $this->faker->randomFloat(2, 1, 100),
            'quantity' => $this->faker->randomNumber(2),
            'image' => UploadedFile::fake()->image('image.jpg'),
        ];
        $response = $this->post('/api/v1/products', $invData, $this->getFormAuthorizationHeader());

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

        $this->assertDatabaseHas('products', ['code' => $response['data']['code']]);

        sleep(1); // Sleep for 1 second to make sure the code is different

        // Create transaction
        $transData = [
            'product_id' => $response['data']['id'],
            'quantity' => 1,
            'image' => UploadedFile::fake()->image('image.jpg'),
        ];
        $response = $this->post('/api/v1/transactions', $transData, $this->getFormAuthorizationHeader());

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'id',
                    'product_id',
                    'code',
                    'type',
                    'price',
                    'quantity',
                    'total',
                    'image',
                ],
            ]);

        sleep(1); // Sleep for 1 second to make sure the code is different

        $transaction = Transaction::find($response['data']['id']);
        $updateData = [
            'quantity' => 2,
            'image' => UploadedFile::fake()->image('image.jpg'),
        ];
        $response = $this->post('/api/v1/transactions/'.$transaction->id, $updateData, $this->getFormAuthorizationHeader());

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'id',
                    'product_id',
                    'code',
                    'type',
                    'price',
                    'quantity',
                    'total',
                    'image',
                ],
            ]);

        // TODO: Check income balance
    }

    public function test_delete_transaction_success()
    {
        $this->refreshDatabase();
        $this->seed();

        // Create product
        $invData = [
            'code' => $this->faker->unique()->word,
            'name' => $this->faker->name,
            'description' => $this->faker->sentence,
            'price' => $this->faker->randomFloat(2, 1, 100),
            'quantity' => $this->faker->randomNumber(2),
            'image' => UploadedFile::fake()->image('image.jpg'),
        ];
        $response = $this->post('/api/v1/products', $invData, $this->getFormAuthorizationHeader());

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

        $this->assertDatabaseHas('products', ['code' => $response['data']['code']]);

        sleep(1); // Sleep for 1 second to make sure the code is different

        // Create transaction
        $transData = [
            'product_id' => $response['data']['id'],
            'quantity' => 1,
            'image' => UploadedFile::fake()->image('image.jpg'),
        ];
        $response = $this->post('/api/v1/transactions', $transData, $this->getFormAuthorizationHeader());

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'id',
                    'product_id',
                    'code',
                    'type',
                    'price',
                    'quantity',
                    'total',
                    'image',
                ],
            ]);

        sleep(1); // Sleep for 1 second to make sure the code is different

        $transaction = Transaction::find($response['data']['id']);
        $response = $this->delete('/api/v1/transactions/'.$transaction->id, $this->getAuthorizationHeader());

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
            ]);

        $this->assertSoftDeleted('transactions', ['id' => $transaction->id]);

        // TODO: Check income balance
    }
}
