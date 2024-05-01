<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\InventoryController;
use App\Http\Controllers\Api\V1\PredictionController;
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
            // Inventory
            Route::resource('inventories', InventoryController::class)->only(['index', 'store', 'show', 'destroy']);
            Route::post('/inventories/{id}', [InventoryController::class, 'update'])->name('inventories.update');

            // Transaction
            Route::resource('transactions', TransactionController::class)->only(['index', 'store', 'show', 'destroy']);
            Route::post('/transactions/{id}', [TransactionController::class, 'update'])->name('transactions.update');

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
