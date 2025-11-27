<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\InventoryController;


Route::prefix('v1')->group(function () {
    Route::post('/inventory', [InventoryController::class, 'store']);
});