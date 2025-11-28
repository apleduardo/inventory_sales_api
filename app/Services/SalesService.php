<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\InventoryLevel;
use App\Models\Product;
use App\Repositories\SalesRepository;
use App\Events\SaleFinalized;
use App\Exceptions\SaleAlreadyRegisteredException;

class SalesService
{
    protected $salesRepository;

    public function __construct(SalesRepository $salesRepository)
    {
        $this->salesRepository = $salesRepository;
    }

    public function registerSale(array $data)
    {
        // Validação básica
        if (empty($data['items']) || !is_array($data['items'])) {
            throw new \Exception('Itens da venda são obrigatórios.');
        }

        return DB::transaction(function () use ($data) {
            $totalAmount = 0;
            $totalProfit = 0;
            $items = [];

            foreach ($data['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);
                if ($product->cost_price === null || $product->sale_price === null) {
                    throw new \Exception('Produto sem preço definido.');
                }
                $inventory = InventoryLevel::where('product_id', $item['product_id'])->where('archived', false)->first();
                if (!$inventory || $inventory->quantity < $item['quantity']) {
                    throw new \Exception('Estoque insuficiente para o produto: ' . $product->name);
                }
                $lineTotal = $item['quantity'] * $item['unit_price'];
                $lineProfit = ($item['unit_price'] - $product->cost_price) * $item['quantity'];
                $totalAmount += $lineTotal;
                $totalProfit += $lineProfit;
                $items[] = [
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'cost_price' => $product->cost_price,
                    'profit' => $lineProfit,
                ];
            }

            $sale = Sale::create([
                'customer_name' => $data['customer_name'] ?? null,
                'transaction_hash' => md5(json_encode($data) . microtime()),
                'total_amount' => $totalAmount,
                'total_profit' => $totalProfit,
                'status' => Sale::STATUS_COMPLETED,
            ]);

            foreach ($items as $item) {
                SaleItem::create(array_merge($item, ['sale_id' => $sale->id]));
                // Atualiza estoque
                $inventory = InventoryLevel::where('product_id', $item['product_id'])->where('archived', false)->first();
                $inventory->quantity -= $item['quantity'];
                $inventory->save();
            }

            Log::info('SALE_COMPLETED', [
                'sale_id' => $sale->id,
                'total_amount' => $totalAmount,
                'total_profit' => $totalProfit,
            ]);

            event(new SaleFinalized($sale));

            return $sale;
        });
    }

    public function createPendingSale(array $data, string $transactionHash)
    {
        return Sale::create([
            'customer_name' => $data['customer_name'] ?? null,
            'transaction_hash' => $transactionHash,
            'total_amount' => 0,
            'total_profit' => 0,
            'status' => Sale::STATUS_PENDING,
        ]);
    }

    /**
     * Fluxo principal de registro de venda pendente e disparo do job, com idempotência.
     */
    public function processSaleRequest(array $data, ?string $transactionHash = null)
    {
        // Gera ou valida o hash
        if (!$transactionHash) {
            $transactionHash = \App\Utils\TransactionHashGenerator::generate($data);
            \Log::info('TRANSACTION_HASH_GENERATED', [
                'data' => $data,
                'transaction_hash' => $transactionHash,
            ]);
        }
        // Idempotência: verifica se já existe venda para o hash
        $existing = \App\Models\Sale::where('transaction_hash', $transactionHash)->first();
        if ($existing) {
            \Log::info('SALE_IDEMPOTENT_RETURN', [
                'transaction_hash' => $transactionHash,
                'sale_id' => $existing->id,
            ]);
            throw new SaleAlreadyRegisteredException($existing);
        }
        // Cria venda pendente
        $sale = $this->createPendingSale($data, $transactionHash);
        \Log::info('SALE_PENDING_CREATED', [
            'sale_id' => $sale->id,
            'transaction_hash' => $transactionHash,
        ]);
        \App\Jobs\ProcessSaleJob::dispatch($sale->id, $data);
        \Log::info('SALE_JOB_DISPATCHED', [
            'sale_id' => $sale->id,
            'transaction_hash' => $transactionHash,
        ]);
        return $sale;
    }

    /**
     * Retorna os detalhes completos de uma venda específica, incluindo itens e produto.
     */
    public function getSaleDetails($id)
    {
        return $this->salesRepository->findSaleWithItems($id);
    }

    /**
     * Gera relatório de vendas com filtros opcionais: data inicial, data final, status, cliente.
     */
    public function getSalesReport(array $filters)
    {
        return $this->salesRepository->getSalesReport($filters);
    }
}
