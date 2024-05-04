<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\ExpenseController;
use App\Http\Controllers\Api\V1\IncomeController;
use App\Http\Controllers\Api\V1\PredictionController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\SettingController;
use App\Http\Controllers\Api\V1\TransactionController;
use App\Models\Setting;
use Illuminate\Support\Facades\Route;

Route::name('api.v1.')
    ->prefix('v1')
    ->group(function () {

        // Auth
        Route::controller(AuthController::class)->prefix('auth')->name('auth.')->group(function () {
            Route::post('login', 'login')->name('login')->middleware('throttle:10,1');

            Route::middleware('auth:sanctum')->group(function () {
                // Logout
                Route::post('logout', 'logout')->name('logout');
            });
        });

        Route::middleware('auth:sanctum')->group(function () {
            // Category
            Route::resource('categories', CategoryController::class);

            // Product
            Route::resource('products', ProductController::class)->only(['index', 'store', 'show', 'destroy']);
            Route::post('/products/{id}', [ProductController::class, 'update'])->name('products.update');

            // Transaction
            Route::resource('transactions', TransactionController::class)->only(['index', 'store', 'show']);

            // Expense
            Route::resource('expenses', ExpenseController::class)->only(['index', 'show']);

            // Income
            Route::resource('incomes', IncomeController::class)->only(['index', 'show']);

            // Dashboard
            Route::controller(DashboardController::class)->prefix('dashboard')->name('dashboard.')->group(function () {
                Route::get('sales-summary', 'salesSummary')->name('sales-summary');
                Route::get('most-sold-products', 'mostSoldProducts')->name('most-sold-products');
                Route::get('most-sold-categories', 'mostSoldCategories')->name('most-sold-categories');
            });

            // Profile
            Route::controller(ProfileController::class)->prefix('profile')->name('profile.')->group(function () {
                Route::get('/', 'index')->name('profile.index');
                Route::post('/', 'update')->name('profile.update');
            });

        });

        // Prediction
        Route::get('/predictions', [PredictionController::class, 'prediction'])->name('predictions.index');

        // Setting
        Route::controller(SettingController::class)->prefix('settings')->name('settings.')->group(function () {
            Route::get('logo', 'logo')->name('logo.index');
            Route::post('logo', 'updateLogo')->name('logo.update');
        });
    });
