<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\InventoryController;
use App\Http\Controllers\Api\V1\SalesController;

Route::prefix('v1')->group(function () {
    Route::post('/inventory', [InventoryController::class, 'store']);
    Route::get('/inventory', [InventoryController::class, 'index']);
    Route::post('/sales', [SalesController::class, 'store']);
});