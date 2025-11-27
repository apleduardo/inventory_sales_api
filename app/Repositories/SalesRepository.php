<?php

namespace App\Repositories;

use App\Models\Sale;
use App\Utils\DateFilterHelper;

class SalesRepository
{
    /**
     * Busca uma venda com seus itens e produtos relacionados
     */
    public function findSaleWithItems($id)
    {
        $sale = Sale::with('items', 'items.product')->find($id);
        if (!$sale) {
            return null;
        }
        return [
            'sale_id' => $sale->id,
            'customer_name' => $sale->customer_name,
            'transaction_hash' => $sale->transaction_hash,
            'total_amount' => $sale->total_amount,
            'total_profit' => $sale->total_profit,
            'status' => $sale->status,
            'created_at' => $sale->created_at,
            'items' => $sale->items->map(function($item) {
                return [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name ?? null,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'cost_price' => $item->cost_price,
                    'profit' => $item->profit,
                ];
            })
        ];
    }

    /**
     * Gera relatório de vendas com filtros opcionais: data inicial, data final, status, cliente.
     */
    public function getSalesReport($filters)
    {
        $query = Sale::with('items', 'items.product');
        if (!empty($filters['start_date'])) {
            $start = DateFilterHelper::normalizeStart($filters['start_date']);
            $query->where('created_at', '>=', $start);
        }
        if (!empty($filters['end_date'])) {
            $end = DateFilterHelper::normalizeEnd($filters['end_date']);
            $query->where('created_at', '<=', $end);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['customer_name'])) {
            $query->where('customer_name', 'like', '%' . $filters['customer_name'] . '%');
        }
        $sales = $query->orderByDesc('created_at')->get();
        return $sales->map(function($sale) {
            return [
                'sale_id' => $sale->id,
                'customer_name' => $sale->customer_name,
                'transaction_hash' => $sale->transaction_hash,
                'total_amount' => $sale->total_amount,
                'total_profit' => $sale->total_profit,
                'status' => $sale->status,
                'created_at' => $sale->created_at,
                'items' => $sale->items->map(function($item) {
                    return [
                        'product_id' => $item->product_id,
                        'product_name' => $item->product->name ?? null,
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price,
                        'cost_price' => $item->cost_price,
                        'profit' => $item->profit,
                    ];
                })
            ];
        });
    }
}
