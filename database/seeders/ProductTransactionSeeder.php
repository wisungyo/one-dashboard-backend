<?php

namespace Database\Seeders;

use App\Enums\TransactionType;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductTransactionSeeder extends Seeder
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
            $incomes = [];
            $outTransactions = [];
            $outTransactionItems = [];

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

                $totalAmount = 0;
                foreach ($products as $product) {
                    $totalAmount += $product->price * $product->quantity;

                    $inTransaction = [
                        'code' => $product->id.'-'.now()->format('YmdHisu'),
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
                    'amount' => $totalAmount,
                    'created_at' => $date,
                    'updated_at' => $date,
                    'created_by' => 1,
                ];
            }
            TransactionItem::insert($inTransactionItems);
            Expense::insert($expenses);

            // Add OUT transaction and the incomes
            $products = Product::all();
            foreach ($products as $product) {
                foreach (range(1, $totalMonth) as $countMonth) {
                    $date = now()->subMonth($countMonth)->startOfMonth();

                    $quantity = round($product->quantity / $totalMonth);
                    $diffQuantity = $product->quantity - $quantity;
                    $totalPrice = $product->price * $quantity;

                    $outTransaction = [
                        'code' => $product->id.'-'.now()->format('YmdHisu'),
                        'type' => TransactionType::OUT,
                        'total_item' => 1,
                        'total_quantity' => $quantity,
                        'total_price' => $totalPrice,
                        'customer_name' => null,
                        'customer_phone' => null,
                        'customer_address' => null,
                        'note' => null,
                        'created_at' => $date,
                        'updated_at' => $date,
                        'created_by' => 1,
                    ];
                    $transaction = Transaction::create($outTransaction);
                    $outTransaction['id'] = $transaction->id;

                    $item = [
                        'transaction_id' => $transaction->id,
                        'product_id' => $product->id,
                        'price' => $product->price,
                        'quantity' => $quantity,
                        'total' => $totalPrice,
                        'created_at' => $date,
                        'updated_at' => $date,
                    ];
                    $outTransactionItems[] = $item;
                    $outTransaction['items'][] = $item;

                    $outTransactions[] = $outTransaction;

                    $dateFormat = $date->format('Y-m-d');
                    if (! isset($incomes[$dateFormat])) {
                        $incomes[$dateFormat] = [
                            'date' => $date,
                            'amount' => $totalPrice,
                            'created_at' => $date,
                            'updated_at' => $date,
                            'created_by' => 1,
                        ];
                    } else {
                        $incomes[$dateFormat]['amount'] += $totalPrice;
                    }

                    $product->quantity = $diffQuantity;
                    $product->save();
                }
            }
            TransactionItem::insert($outTransactionItems);
            Income::insert($incomes);

            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }
}
