<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\DTOs\InventoryEntryDTO;
use App\Services\InventoryService;
use Illuminate\Support\Facades\Log;

class InventoryController extends Controller
{    
    protected $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }
    
    public function index()
    {
        $summary = $this->inventoryService->getInventorySummary();
        return response()->json($summary);
    }

    public function store(InventoryEntryDTO $data, Request $request)
    {
        try {
            Log::info('INVENTORY_DEBUG', [
                'data' => $data->toArray(),
                'request' => $request->all(),
            ]);

            Log::info('API_REQUEST_RECEIVED', [
                'event' => 'API_REQUEST_RECEIVED',
                'endpoint' => $request->path(),
                'method' => $request->method(), 
                'validated_data' => $data->toArray(),
                'ip_address' => $request->ip(),
                'user_id' => auth()->check() ? auth()->id() : 'guest',
                'request_time' => now()->toDateTimeString(),
            ]);

            $movement = $this->inventoryService->registerEntry(
                $data->product_id, 
                $data->quantity, 
                $data->cost_price
            );

            return response()->json([
                'message' => 'Entrada de estoque registrada com sucesso.',
                'movement_id' => $movement->id
            ], 201);

        } catch (\Exception $e) {
            Log::error('INVENTORY_ERROR', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['message' => 'Falha ao registrar entrada.', 'error' => $e->getMessage()], 500);
        }
     }
}
