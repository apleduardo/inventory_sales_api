<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\SalesService;
use Illuminate\Support\Facades\Log;
use App\DTOs\SalesEntryDTO;
use Illuminate\Validation\ValidationException;
use App\Utils\TransactionHashGenerator;
use App\Exceptions\SaleAlreadyRegisteredException;

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
            $transactionHash = $request->input('transaction_hash');
            $sale = $this->salesService->processSaleRequest($data, $transactionHash);
            return response()->json([
                'message' => 'Venda recebida e será processada.',
                'sale_id' => $sale->id,
                'transaction_hash' => $sale->transaction_hash
            ], 202);
        } catch (SaleAlreadyRegisteredException $e) {
            return response()->json([
                'message' => 'Venda já registrada para este hash.',
                'sale_id' => $e->getSale()->id,
                'transaction_hash' => $e->getSale()->transaction_hash
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Erro de validação.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('SALES_ERROR', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['message' => 'Falha ao registrar venda.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/v1/sales/{id}
     * Retorna os detalhes completos de uma venda específica.
     */
    public function show($id)
    {
        $sale = $this->salesService->getSaleDetails($id);
        if (!$sale) {
            return response()->json(['message' => 'Venda não encontrada.'], 404);
        }
        return response()->json($sale);
    }

    /**
     * GET /api/v1/reports/sales
     * Gera relatório de vendas com filtros opcionais: data inicial, data final, status, cliente.
     * Exemplo de uso: /api/v1/reports/sales?start_date=2025-11-01&end_date=2025-11-27&status=COMPLETED&customer_name=João
     */
    public function report(Request $request)
    {
        $filters = [
            'start_date' => $request->query('start_date'),
            'end_date' => $request->query('end_date'),
            'status' => $request->query('status'),
            'customer_name' => $request->query('customer_name'),
        ];
        $report = $this->salesService->getSalesReport($filters);
        return response()->json($report);
    }
}
