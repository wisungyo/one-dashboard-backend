<?php

namespace App\Http\Services\Api\V1;

use App\Enums\TransactionType;
use App\Http\Resources\Api\V1\ProductResource;
use App\Models\Product;
use App\Models\TransactionItem;
use Illuminate\Support\Facades\Log;

class PredictionService extends BaseResponse
{
    const MONTH_PERIODE = 3;

    const ALPHA = 0.1;

    const KEY_TOTAL = 'total_quantity';

    const KEY_PREDICTION = 'prediction_next_month';

    const KEY_PREDICTION_VALUE = 'prediction_value';

    // Function to find index based on a condition
    public function findIndex($dataArray, $conditionCallback)
    {
        foreach ($dataArray as $index => $element) {
            if ($conditionCallback($element)) {
                return $index;
            }
        }

        return -1; // Return -1 if no matching element is found
    }

    /**
     * Exponential smoothing calculation
     */
    public function exponentialSmoothing($data, $alpha = self::ALPHA)
    {
        $n = count($data);
        $predictions = [];
        if ($n == 0) {
            return $predictions;
        }
        $predictions[0] = $data[0];
        $predictions[0][self::KEY_PREDICTION] = intval($data[0][self::KEY_TOTAL]);
        for ($i = 1; $i < $n; $i++) {
            $predictions[$i] = $data[$i];
            $predictions[$i][self::KEY_PREDICTION] = $alpha * $data[$i][self::KEY_TOTAL] + (1 - $alpha) * $predictions[$i - 1][self::KEY_PREDICTION];
        }

        return $predictions;
    }

    /**
     * Calculate prediction value for the next month based on the last x months
     *
     * @param  array  $params
     */
    public function calculatePrediction($params)
    {
        try {
            $year = $params['year'];
            $month = $params['month'];
            $page = intval($params['page']);
            $limit = intval($params['limit']);

            // Initialize timestamp
            $latestTimestamp = strtotime("$year-$month-01");
            $firstTimestamp = strtotime('-'.self::MONTH_PERIODE.' month', $latestTimestamp);

            // Set year and month (Y-m) combination based on the range of timestamp to check if there is any missing month in the range
            $yearMonth = [];
            for ($i = 0; $i < self::MONTH_PERIODE; $i++) {
                $yearMonth[] = date('Y-m', strtotime("+$i month", $firstTimestamp));
            }

            // Sum existing transaction item quantity based on the range of timestamp
            $transactionItems = TransactionItem::selectRaw('product_id, SUM(quantity) as '.self::KEY_TOTAL.', YEAR(transaction_items.created_at) as year, MONTH(transaction_items.created_at) as month, DATE_FORMAT(transaction_items.created_at, "%Y-%m") as current_month, DATE_FORMAT(DATE_ADD(transaction_items.created_at, INTERVAL 1 MONTH), "%Y-%m") AS next_month')
                ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
                ->where('transactions.type', TransactionType::OUT)
                ->whereBetween('transaction_items.created_at', [date('Y-m-d', $firstTimestamp), date('Y-m-d', $latestTimestamp)])
                ->groupBy('product_id', 'year', 'month', 'current_month', 'next_month')
                ->orderBy('current_month', 'asc')
                ->get()
                ->toArray();

            // Grouping transaction items by product id
            $transactionItemsGrouped = [];
            foreach ($transactionItems as $item) {
                $index = $this->findIndex($transactionItemsGrouped, function ($element) use ($item) {
                    return $element['product_id'] == $item['product_id'];
                });
                if ($index == -1) {
                    $transactionItemsGrouped[] = [
                        'product_id' => $item['product_id'],
                        'data' => [$item],
                    ];
                } else {
                    $transactionItemsGrouped[$index]['data'][] = $item;
                }
            }

            // Set default value for missing year month combination and do sorting
            $transactionItemsGroupedSorted = [];
            foreach ($transactionItemsGrouped as $item) {
                $data = $item['data'];
                $currentMonth = array_column($data, 'current_month');
                $missingMonth = array_diff($yearMonth, $currentMonth);
                foreach ($missingMonth as $month) {
                    $data[] = [
                        'product_id' => $item['product_id'],
                        self::KEY_TOTAL => 0,
                        'year' => $year,
                        'month' => date('m', strtotime($month)),
                        'current_month' => $month,
                        'next_month' => date('Y-m', strtotime($month.' +1 month')),
                    ];
                }
                usort($data, function ($a, $b) {
                    return $a['current_month'] <=> $b['current_month'];
                });
                $transactionItemsGroupedSorted[] = [
                    'product_id' => $item['product_id'],
                    'data' => $data,
                ];
            }

            $productsSummary = [];
            foreach ($transactionItemsGroupedSorted as $item) {
                // Do exponential smoothing calculation
                $predictions = $this->exponentialSmoothing($item['data']);

                // Find prediction value for the search month
                $predictionValue = $predictions[count($predictions) - 1][self::KEY_PREDICTION];

                // Set total sold with diff from previously
                $soldSummary = [];
                foreach ($predictions as $index => $prediction) {
                    $previousSold = $index == 0 ? 0 : $predictions[$index - 1][self::KEY_TOTAL];
                    $sold = $prediction[self::KEY_TOTAL];
                    $increaseSold = 0;
                    $increaseSoldPercentage = 0;

                    if ($index > 0) {
                        $increaseSold = $sold - $previousSold;
                        $increaseSoldPercentage = $previousSold == 0 ? 100 : ($increaseSold / $previousSold) * 100;
                    }

                    $soldSummary[] = [
                        'previous_sold' => $previousSold,
                        'sold' => $sold,
                        'increase_sold' => $increaseSold,
                        'increase_sold_percentage' => $increaseSoldPercentage,
                    ];
                }

                $summary = [
                    'product_id' => $item['product_id'],
                    self::KEY_PREDICTION_VALUE => $predictionValue,
                    'sold_summary' => $soldSummary,
                    'product' => null, // Will be filled later
                    'predictions' => $predictions,
                ];

                $productsSummary[] = $summary;
            }

            // Get all product based on transaction items
            $productIds = array_unique(array_column($productsSummary, 'product_id'));
            $products = Product::with('category')->whereIn('id', $productIds)->get();
            $productResources = [];

            foreach ($products as $product) {
                $productResources[$product->id] = new ProductResource($product);
            }

            // Mapping product resource to product summary
            foreach ($productsSummary as $key => $productSummary) {
                $productsSummary[$key]['product'] = $productResources[$productSummary['product_id']] ?? null;
            }

            // Do pagination based on limit and page for the products summary
            $total = count($productsSummary);
            $productsSummary = array_slice($productsSummary, ($page - 1) * $limit, $limit);
            $paginationData = [
                'total' => $total,
                'total_page' => ceil($total / $limit),
                'page' => $page,
                'items' => $limit,
                'limit' => $limit,
            ];

            $resp = [
                'latest_timestamp' => date('Y-m-d', $latestTimestamp),
                'first_timestamp' => date('Y-m-d', $firstTimestamp),
                'products_summary' => $productsSummary,
                'pagination' => $paginationData,
            ];

            return $this->responseSuccess(__('Get prediction value successfully'), 200, $resp);
        } catch (\Throwable $th) {
            Log::error($th);

            return $this->responseError(__('Get prediction value failed'), 500, $th->getMessage());
        }
    }
}
