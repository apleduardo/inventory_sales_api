<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\SalesService;
use Illuminate\Support\Facades\Log;
use App\DTOs\SalesEntryDTO;
use Illuminate\Validation\ValidationException;
use App\Utils\TransactionHashGenerator;

class SalesController extends Controller
{
    protected $salesService;

    public function __construct(SalesService $salesService)
    {
        $this->salesService = $salesService;
    }

    public function store(SalesEntryDTO $dto, Request $request)
    {
        try {
            $data = [
                'customer_name' => $dto->customer_name,
                'items' => $dto->items
            ];
            $transactionHash = $request->input('transaction_hash') ?? TransactionHashGenerator::generate($data);
            Log::info('SALES_REQUEST_RECEIVED', [
                'endpoint' => $request->path(),
                'method' => $request->method(),
                'data' => $data,
                'ip_address' => $request->ip(),
                'user_id' => auth()->check() ? auth()->id() : 'guest',
                'request_time' => now()->toDateTimeString(),
                'transaction_hash' => $transactionHash,
            ]);
            $existing = \App\Models\Sale::where('transaction_hash', $transactionHash)->first();
            if ($existing) {
                return response()->json([
                    'message' => 'Venda já registrada para este hash.',
                    'sale_id' => $existing->id,
                    'transaction_hash' => $transactionHash
                ], 200);
            }
            $sale = $this->salesService->createPendingSale($data, $transactionHash);
            \App\Jobs\ProcessSaleJob::dispatch($sale->id, $data);
            return response()->json([
                'message' => 'Venda recebida e será processada.',
                'sale_id' => $sale->id,
                'transaction_hash' => $transactionHash
            ], 202);
        } catch (\Exception $e) {
            Log::error('SALES_ERROR', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['message' => 'Falha ao registrar venda.', 'error' => $e->getMessage()], 500);
        }
    }
}
