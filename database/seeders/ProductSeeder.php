<?php

namespace Database\Seeders;

use App\Enums\TransactionType;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::beginTransaction();
        try {
            $totalMonth = 6;
            $totalProducts = 20;

            $expenses = [];
            $inTransactions = [];
            $inTransactionItems = [];

            // Add products, IN transactions and expense
            foreach (range(1, $totalMonth) as $countMonth) {
                $date = now()->subMonth($countMonth)->startOfMonth();

                $products = Product::factory()->count($totalProducts)->create(
                    [
                        'created_at' => $date,
                        'updated_at' => $date,
                        'created_by' => 1,
                    ]
                );

                $totalItem = 0;
                $totalQuantity = 0;
                $totalAmount = 0;
                foreach ($products as $product) {
                    $totalItem++;
                    $totalQuantity += $product->quantity;
                    $totalAmount += $product->price * $product->quantity;

                    $inTransaction = [
                        'code' => $product->id.'-'.TransactionType::IN->value.'-'.now()->format('YmdHisu'),
                        'type' => TransactionType::IN,
                        'total_item' => 1,
                        'total_quantity' => $product->quantity,
                        'total_price' => $product->price,
                        'customer_name' => null,
                        'customer_phone' => null,
                        'customer_address' => null,
                        'note' => null,
                        'created_at' => $date,
                        'updated_at' => $date,
                        'created_by' => 1,
                    ];
                    $transaction = Transaction::create($inTransaction);
                    $inTransaction['id'] = $transaction->id;

                    $item = [
                        'transaction_id' => $transaction->id,
                        'product_id' => $product->id,
                        'price' => $product->price,
                        'quantity' => $product->quantity,
                        'total' => $product->price * $product->quantity,
                        'created_at' => $date,
                        'updated_at' => $date,
                    ];
                    $inTransactionItems[] = $item;
                    $inTransaction['items'][] = $item;

                    $inTransactions[] = $inTransaction;
                }

                $expenses[] = [
                    'date' => $date,
                    'total_item' => $totalItem,
                    'total_quantity' => $totalQuantity,
                    'amount' => $totalAmount,
                    'created_at' => $date,
                    'updated_at' => $date,
                    'created_by' => 1,
                ];
            }
            TransactionItem::insert($inTransactionItems);
            Expense::insert($expenses);

            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }
}
