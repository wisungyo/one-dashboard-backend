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
use Illuminate\Support\Facades\Log;

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

            // Add OUT transaction and the incomes
            $products = Product::all();
            foreach ($products as $product) {
                foreach (range(1, $totalMonth) as $countMonth) {
                    $startOfMonth = now()->subMonth($countMonth)->startOfMonth();
                    $endOfMonth = now()->subMonth($countMonth)->endOfMonth();

                    $date = $startOfMonth;
                    while ($date <= $endOfMonth) {
                        if ($product->quantity <= 0){
                            break;
                        }

                        $randomQty = random_int(0, 3);
                        Log::info('Random qty: '.$randomQty);
                        if ($randomQty == 0) {
                            $date = $date->addDay();
                            continue;
                        }

                        $quantity = $randomQty;
                        if ($product->quantity < $quantity) {
                            $quantity = $product->quantity;
                        }

                        $diffQuantity = $product->quantity - $quantity;
                        $totalItem = 1;
                        $totalQuantity = $quantity;
                        $totalPrice = $product->price * $quantity;

                        $outTransaction = [
                            'code' => $product->id.'-'.TransactionType::OUT->value.'-'.now()->format('YmdHisu'),
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
                        Log::info("created transaction: ".$transaction->id);
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

                        $product->quantity = $diffQuantity;
                        $product->save();
                        Log::info("save product: ".$product->id." qty: ".$product->quantity);
                        
                        $date = $date->addDay();
                        Log::info("new date ".$date->format('Y-m-d'));
                    }
                }
            }

            Log::info('Out transactions len: '.count($outTransactions));
            TransactionItem::insert($outTransactionItems);

            foreach ($outTransactions as $outTransaction) {
                $date = $outTransaction['created_at'];
                $dateFormat = $date->format('Y-m-d');
                if (! isset($incomes[$dateFormat])) {
                    Log::info("add income: ".$dateFormat);
                    $incomes[$dateFormat] = [
                        'date' => $outTransaction['created_at'],
                        'total_item' => $outTransaction['total_item'],
                        'total_quantity' => $outTransaction['total_quantity'],
                        'amount' => $outTransaction['total_price'],
                        'created_at' => $outTransaction['created_at'],
                        'updated_at' => $outTransaction['updated_at'],
                        'created_by' => 1,
                    ];
                } else {
                    Log::info("update income: ".$dateFormat);
                    $incomes[$dateFormat]['total_item'] += $outTransaction['total_item'];
                    $incomes[$dateFormat]['total_quantity'] += $outTransaction['total_quantity'];
                    $incomes[$dateFormat]['amount'] += $outTransaction['total_price'];
                }
            }
            Log::info('Incomes len: '.count($incomes));
            Income::insert($incomes);

            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }
}
