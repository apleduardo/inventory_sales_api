<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\InventoryController;
use App\Http\Controllers\Api\V1\SalesController;
use App\Http\Controllers\Api\V1\AuthController;

Route::prefix('v1')->group(function () {
    Route::post('/inventory', [InventoryController::class, 'store']);
    Route::get('/inventory', [InventoryController::class, 'index']);
    Route::post('/sales', [SalesController::class, 'store']);
    // Endpoint para obter detalhes de uma venda específica
    Route::get('/sales/{id}', [SalesController::class, 'show']);
    // Endpoint para relatório de vendas com filtros
    Route::get('/reports/sales', [SalesController::class, 'report']);
    
    Route::post('login', [AuthController::class, 'login']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
    });
});